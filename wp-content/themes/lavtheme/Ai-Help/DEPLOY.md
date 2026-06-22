# DEPLOY.md — shipping lavtheme to the host

> Part of the **[Ai-Help](README.md)** doc set. How to get local changes onto the
> live site (`https://lavzen.com`, Hostinger) **safely**. Supersedes the old
> `sftp-batch.txt` / `sftp-commands.txt` scripts, which were removed because they
> used a **broken path** (see "Pitfalls").

---

## 0. Before you upload — lint

PHP **8.3 CLI is available locally**, so lint every changed PHP file first. A
syntax error in a bootstrap file (`functions.php`, an `inc/*.php`) white-screens
the whole site.

```bash
# from the theme root
for f in $(git diff --name-only '*.php'); do php -l "$f"; done
# JS too, if node is available:
for f in $(git diff --name-only '*.js'); do node --check "$f"; done
```

(Code Studio also lints on save via `token_get_all(TOKEN_PARSE)`, but lint
locally before FTP so you never ship a fatal.)

---

## 1. The connection (verified Jun 2026)

| | |
|---|---|
| Protocol | **plain FTP, port 21** (not SFTP/SSH — no port-22 credentials exist) |
| Host | `82.29.185.21` |
| User | `u523965318.lavzen.com` |
| Theme path | **`/wp-content/themes/lavtheme/`** |

The FTP account **chroots to the domain web root**, so the FTP login dir `/` **is**
`…/lavzen.com/public_html/` (it contains `wp-admin`, `wp-config.php`,
`wp-content`). Therefore the theme is at `/wp-content/themes/lavtheme/` **relative
to the FTP root**.

> 🔐 **Security:** `ftp-upload.txt` stores the password in plaintext. Keep it out
> of any public repo — add `Ai-Help/ftp-upload.txt` to `.gitignore`, or move the
> credentials to an environment variable / local-only file. Rotate the password if
> it has ever been committed.

---

## 2. Upload (curl, the reliable form)

```bash
USER='u523965318.lavzen.com'
PASS='...'                      # from ftp-upload.txt (keep secret)
BASE="ftp://82.29.185.21/wp-content/themes/lavtheme"

# one file
curl -T assets/css/account.css -u "$USER:$PASS" "$BASE/assets/css/account.css"

# many files (run from the theme root)
for f in path/one.css path/two.php; do
  curl -sS --ftp-create-dirs -T "$f" -u "$USER:$PASS" "$BASE/$f" && echo "OK $f"
done
```

Verify a file actually landed (don't trust "it should have"):

```bash
curl -sS -u "$USER:$PASS" "$BASE/assets/css/account.css" | head
```

---

## 3. After uploading — clear caches

- **OPcache** picks up correct-path PHP uploads automatically (a parse-error file
  recompiles every request, so a fix lands immediately).
- **hcdn (Hostinger CDN / LiteSpeed)** caches **static assets** (`.css`/`.js`) and
  **ignores `?cb=`** for them. After changing CSS/JS you **must purge** from
  hPanel → Cache, or the change appears not to have applied.
- Inline CSS/JS (the composed base + injected context code) lives in the HTML
  document, so it's subject to the **page** cache — a full purge covers both.

---

## 4. Pitfalls (these have bitten before)

- ❌ **Never `cd public_html/...`** There is a stray nested `public_html/` *inside*
  the FTP root that the web server does **not** serve. Uploading there silently
  changes nothing (the site never updates, probes 404). The deleted
  `sftp-batch.txt` / `sftp-commands.txt` had exactly this bug, plus a wrong port 22.
- ❌ **"My change didn't apply"** → check the **path** and the **hcdn cache** before
  suspecting your code. Re-fetch the file over FTP (step 2) to confirm bytes.
- ❌ **Code Studio override shadows a file.** If a context/section was edited in
  Code Studio, its DB override wins over the uploaded file. Use **Reset to default**
  (or re-save) in that section so it reads the fresh file. See
  [AI_CONTEXT2.md](AI_CONTEXT2.md).
- ❌ **`main.css` is rollback-only.** In the default inline-CSS mode it is **not**
  enqueued; editing it has no live effect unless `LAVTHEME_DISABLE_INLINE_CSS` is
  set. Edit the split files under `assets/css/sections/` instead.

---

## 5. Quick checklist

1. `php -l` (and `node --check`) every changed file — clean.
2. Upload via curl to `…/wp-content/themes/lavtheme/<relpath>`.
3. Re-fetch one file to confirm it landed.
4. **Purge hcdn cache** (hPanel) for any CSS/JS change.
5. Spot-check the affected page in a private window.

*See also: [AI_CONTEXT.md](AI_CONTEXT.md) §"Critical gotchas", [README.md](README.md).*
