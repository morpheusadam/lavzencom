# ✅ گایدلاین‌های UX (۹۸ قانون)

۹۸ قانون UX/دسترسی‌پذیری با Do / Don't و شدت اهمیت. مرجع کنترل کیفیت.

> منبع کامل (همه‌ی ستون‌ها): [`data/ux-guidelines.csv`](../data/ux-guidelines.csv) — 99 ردیف

| دسته | موضوع | پلتفرم | شدت | توضیح | بکن ✓ | نکن ✗ |
|---|---|---|---|---|---|---|
| Navigation | Smooth Scroll | Web | High | Anchor links should scroll smoothly to target section | Use scroll-behavior: smooth on html element | Jump directly without transition |
| Navigation | Sticky Navigation | Web | Medium | Fixed nav should not obscure content | Add padding-top to body equal to nav height | Let nav overlap first section content |
| Navigation | Active State | All | Medium | Current page/section should be visually indicated | Highlight active nav item with color/underline | No visual feedback on current location |
| Navigation | Back Button | Mobile | High | Users expect back to work predictably | Preserve navigation history properly | Break browser/app back button behavior |
| Navigation | Deep Linking | All | Medium | URLs should reflect current state for sharing | Update URL on state/view changes | Static URLs for dynamic content |
| Navigation | Breadcrumbs | Web | Low | Show user location in site hierarchy | Use for sites with 3+ levels of depth | Use for flat single-level sites |
| Animation | Excessive Motion | All | High | Too many animations cause distraction and motion sickness | Animate 1-2 key elements per view maximum | Animate everything that moves |
| Animation | Duration Timing | All | Medium | Animations should feel responsive not sluggish | Use 150-300ms for micro-interactions | Use animations longer than 500ms for UI |
| Animation | Reduced Motion | All | High | Respect user's motion preferences | Check prefers-reduced-motion media query | Ignore accessibility motion settings |
| Animation | Loading States | All | High | Show feedback during async operations | Use skeleton screens or spinners | Leave UI frozen with no feedback |
| Animation | Hover vs Tap | All | High | Hover effects don't work on touch devices | Use click/tap for primary interactions | Rely only on hover for important actions |
| Animation | Continuous Animation | All | Medium | Infinite animations are distracting | Use for loading indicators only | Use for decorative elements |
| Animation | Transform Performance | Web | Medium | Some CSS properties trigger expensive repaints | Use transform and opacity for animations | Animate width/height/top/left properties |
| Animation | Easing Functions | All | Low | Linear motion feels robotic | Use ease-out for entering ease-in for exiting | Use linear for UI transitions |
| Layout | Z-Index Management | Web | High | Stacking context conflicts cause hidden elements | Define z-index scale system (10 20 30 50) | Use arbitrary large z-index values |
| Layout | Overflow Hidden | Web | Medium | Hidden overflow can clip important content | Test all content fits within containers | Blindly apply overflow-hidden |
| Layout | Fixed Positioning | Web | Medium | Fixed elements can overlap or be inaccessible | Account for safe areas and other fixed elements | Stack multiple fixed elements carelessly |
| Layout | Stacking Context | Web | Medium | New stacking contexts reset z-index | Understand what creates new stacking context | Expect z-index to work across contexts |
| Layout | Content Jumping | Web | High | Layout shift when content loads is jarring | Reserve space for async content | Let images/content push layout around |
| Layout | Viewport Units | Web | Medium | 100vh can be problematic on mobile browsers | Use dvh or account for mobile browser chrome | Use 100vh for full-screen mobile layouts |
| Layout | Container Width | Web | Medium | Content too wide is hard to read | Limit max-width for text content (65-75ch) | Let text span full viewport width |
| Touch | Touch Target Size | Mobile | High | Small buttons are hard to tap accurately | Minimum 44x44px touch targets | Tiny clickable areas |
| Touch | Touch Spacing | Mobile | Medium | Adjacent touch targets need adequate spacing | Minimum 8px gap between touch targets | Tightly packed clickable elements |
| Touch | Gesture Conflicts | Mobile | Medium | Custom gestures can conflict with system | Avoid horizontal swipe on main content | Override system gestures |
| Touch | Tap Delay | Mobile | Medium | 300ms tap delay feels laggy | Use touch-action CSS or fastclick | Default mobile tap handling |
| Touch | Pull to Refresh | Mobile | Low | Accidental refresh is frustrating | Disable where not needed | Enable by default everywhere |
| Touch | Haptic Feedback | Mobile | Low | Tactile feedback improves interaction feel | Use for confirmations and important actions | Overuse vibration feedback |
| Interaction | Focus States | All | High | Keyboard users need visible focus indicators | Use visible focus rings on interactive elements | Remove focus outline without replacement |
| Interaction | Hover States | Web | Medium | Visual feedback on interactive elements | Change cursor and add subtle visual change | No hover feedback on clickable elements |
| Interaction | Active States | All | Medium | Show immediate feedback on press/click | Add pressed/active state visual change | No feedback during interaction |
| Interaction | Disabled States | All | Medium | Clearly indicate non-interactive elements | Reduce opacity and change cursor | Confuse disabled with normal state |
| Interaction | Loading Buttons | All | High | Prevent double submission during async actions | Disable button and show loading state | Allow multiple clicks during processing |
| Interaction | Error Feedback | All | High | Users need to know when something fails | Show clear error messages near problem | Silent failures with no feedback |
| Interaction | Success Feedback | All | Medium | Confirm successful actions to users | Show success message or visual change | No confirmation of completed action |
| Interaction | Confirmation Dialogs | All | High | Prevent accidental destructive actions | Confirm before delete/irreversible actions | Delete without confirmation |
| Accessibility | Color Contrast | All | High | Text must be readable against background | Minimum 4.5:1 ratio for normal text | Low contrast text |
| Accessibility | Color Only | All | High | Don't convey information by color alone | Use icons/text in addition to color | Red/green only for error/success |
| Accessibility | Alt Text | All | High | Images need text alternatives | Descriptive alt text for meaningful images | Empty or missing alt attributes |
| Accessibility | Heading Hierarchy | Web | Medium | Screen readers use headings for navigation | Use sequential heading levels h1-h6 | Skip heading levels or misuse for styling |
| Accessibility | ARIA Labels | All | High | Interactive elements need accessible names | Add aria-label for icon-only buttons | Icon buttons without labels |
| Accessibility | Keyboard Navigation | Web | High | All functionality accessible via keyboard | Tab order matches visual order | Keyboard traps or illogical tab order |
| Accessibility | Screen Reader | All | Medium | Content should make sense when read aloud | Use semantic HTML and ARIA properly | Div soup with no semantics |
| Accessibility | Form Labels | All | High | Inputs must have associated labels | Use label with for attribute or wrap input | Placeholder-only inputs |
| Accessibility | Error Messages | All | High | Error messages must be announced | Use aria-live or role=alert for errors | Visual-only error indication |
| Accessibility | Skip Links | Web | Medium | Allow keyboard users to skip navigation | Provide skip to main content link | No skip link on nav-heavy pages |
| Performance | Image Optimization | All | High | Large images slow page load | Use appropriate size and format (WebP) | Unoptimized full-size images |
| Performance | Lazy Loading | All | Medium | Load content as needed | Lazy load below-fold images and content | Load everything upfront |
| Performance | Code Splitting | Web | Medium | Large bundles slow initial load | Split code by route/feature | Single large bundle |
| Performance | Caching | Web | Medium | Repeat visits should be fast | Set appropriate cache headers | No caching strategy |
| Performance | Font Loading | Web | Medium | Web fonts can block rendering | Use font-display swap or optional | Invisible text during font load |
| Performance | Third Party Scripts | Web | Medium | External scripts can block rendering | Load non-critical scripts async/defer | Synchronous third-party scripts |
| Performance | Bundle Size | Web | Medium | Large JavaScript slows interaction | Monitor and minimize bundle size | Ignore bundle size growth |
| Performance | Render Blocking | Web | Medium | CSS/JS can block first paint | Inline critical CSS defer non-critical | Large blocking CSS files |
| Forms | Input Labels | All | High | Every input needs a visible label | Always show label above or beside input | Placeholder as only label |
| Forms | Error Placement | All | Medium | Errors should appear near the problem | Show error below related input | Single error message at top of form |
| Forms | Inline Validation | All | Medium | Validate as user types or on blur | Validate on blur for most fields | Validate only on submit |
| Forms | Input Types | All | Medium | Use appropriate input types | Use email tel number url etc | Text input for everything |
| Forms | Autofill Support | Web | Medium | Help browsers autofill correctly | Use autocomplete attribute properly | Block or ignore autofill |
| Forms | Required Indicators | All | Medium | Mark required fields clearly | Use asterisk or (required) text | No indication of required fields |
| Forms | Password Visibility | All | Medium | Let users see password while typing | Toggle to show/hide password | No visibility toggle |
| Forms | Submit Feedback | All | High | Confirm form submission status | Show loading then success/error state | No feedback after submit |
| Forms | Input Affordance | All | Medium | Inputs should look interactive | Use distinct input styling | Inputs that look like plain text |
| Forms | Mobile Keyboards | Mobile | Medium | Show appropriate keyboard for input type | Use inputmode attribute | Default keyboard for all inputs |
| Responsive | Mobile First | Web | Medium | Design for mobile then enhance for larger | Start with mobile styles then add breakpoints | Desktop-first causing mobile issues |
| Responsive | Breakpoint Testing | Web | Medium | Test at all common screen sizes | Test at 320 375 414 768 1024 1440 | Only test on your device |
| Responsive | Touch Friendly | Web | High | Mobile layouts need touch-sized targets | Increase touch targets on mobile | Same tiny buttons on mobile |
| Responsive | Readable Font Size | All | High | Text must be readable on all devices | Minimum 16px body text on mobile | Tiny text on mobile |
| Responsive | Viewport Meta | Web | High | Set viewport for mobile devices | Use width=device-width initial-scale=1 | Missing or incorrect viewport |
| Responsive | Horizontal Scroll | Web | High | Avoid horizontal scrolling | Ensure content fits viewport width | Content wider than viewport |
| Responsive | Image Scaling | Web | Medium | Images should scale with container | Use max-width: 100% on images | Fixed width images overflow |
| Responsive | Table Handling | Web | Medium | Tables can overflow on mobile | Use horizontal scroll or card layout | Wide tables breaking layout |
| Typography | Line Height | All | Medium | Adequate line height improves readability | Use 1.5-1.75 for body text | Cramped or excessive line height |
| Typography | Line Length | Web | Medium | Long lines are hard to read | Limit to 65-75 characters per line | Full-width text on large screens |
| Typography | Font Size Scale | All | Medium | Consistent type hierarchy aids scanning | Use consistent modular scale | Random font sizes |
| Typography | Font Loading | Web | Medium | Fonts should load without layout shift | Reserve space with fallback font | Layout shift when fonts load |
| Typography | Contrast Readability | All | High | Body text needs good contrast | Use darker text on light backgrounds | Gray text on gray background |
| Typography | Heading Clarity | All | Medium | Headings should stand out from body | Clear size/weight difference | Headings similar to body text |
| Feedback | Loading Indicators | All | High | Show system status during waits | Show spinner/skeleton for operations > 300ms | No feedback during loading |
| Feedback | Empty States | All | Medium | Guide users when no content exists | Show helpful message and action | Blank empty screens |
| Feedback | Error Recovery | All | Medium | Help users recover from errors | Provide clear next steps | Error without recovery path |
| Feedback | Progress Indicators | All | Medium | Show progress for multi-step processes | Step indicators or progress bar | No indication of progress |
| Feedback | Toast Notifications | All | Medium | Transient messages for non-critical info | Auto-dismiss after 3-5 seconds | Toasts that never disappear |
| Feedback | Confirmation Messages | All | Medium | Confirm successful actions | Brief success message | Silent success |
| Content | Truncation | All | Medium | Handle long content gracefully | Truncate with ellipsis and expand option | Overflow or broken layout |
| Content | Date Formatting | All | Low | Use locale-appropriate date formats | Use relative or locale-aware dates | Ambiguous date formats |
| Content | Number Formatting | All | Low | Format large numbers for readability | Use thousand separators or abbreviations | Long unformatted numbers |
| Content | Placeholder Content | All | Low | Show realistic placeholders during dev | Use realistic sample data | Lorem ipsum everywhere |
| Onboarding | User Freedom | All | Medium | Users should be able to skip tutorials | Provide Skip and Back buttons | Force linear unskippable tour |
| Search | Autocomplete | Web | Medium | Help users find results faster | Show predictions as user types | Require full type and enter |
| Search | No Results | Web | Medium | Dead ends frustrate users | Show 'No results' with suggestions | Blank screen or '0 results' |
| Data Entry | Bulk Actions | Web | Low | Editing one by one is tedious | Allow multi-select and bulk edit | Single row actions only |
| AI Interaction | Disclaimer | All | High | Users need to know they talk to AI | Clearly label AI generated content | Present AI as human |
| AI Interaction | Streaming | All | Medium | Waiting for full text is slow | Stream text response token by token | Show loading spinner for 10s+ |
| Spatial UI | Gaze Hover | VisionOS | High | Elements should respond to eye tracking before pinch | Scale/highlight element on look | Static element until pinch |
| Spatial UI | Depth Layering | VisionOS | Medium | UI needs Z-depth to separate content from environment | Use glass material and z-offset | Flat opaque panels blocking view |
| Sustainability | Auto-Play Video | Web | Medium | Video consumes massive data and energy | Click-to-play or pause when off-screen | Auto-play high-res video loops |
| Sustainability | Asset Weight | Web | Medium | Heavy 3D/Image assets increase carbon footprint | Compress and lazy load 3D models | Load 50MB textures |
| AI Interaction | Feedback Loop | All | Low | AI needs user feedback to improve | Thumps up/down or 'Regenerate' | Static output only |
| Accessibility | Motion Sensitivity | All | High | Parallax/Scroll-jacking causes nausea | Respect prefers-reduced-motion | Force scroll effects |
