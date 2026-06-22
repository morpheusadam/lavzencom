# 📱 رابط اپلیکیشن (App Interface)

۲۹ راهنمای رابط نیتیو/اپ موبایل (touch, safe-area, accessibility) با Do/Don't.

> منبع کامل (همه‌ی ستون‌ها): [`data/app-interface.csv`](../data/app-interface.csv) — 30 ردیف

| دسته | موضوع | پلتفرم | شدت | توضیح | بکن ✓ | نکن ✗ |
|---|---|---|---|---|---|---|
| Accessibility | Icon Button Labels | iOS/Android/React Native | Critical | Icon-only buttons must expose an accessible label | Set accessibilityLabel or label prop on icon buttons | Icon buttons without accessible names |
| Accessibility | Form Control Labels | iOS/Android/React Native | Critical | All inputs must have a visible label and an accessibility label | Pair Text label with input and set accessibilityLabel | Inputs with placeholder only |
| Accessibility | Role & Traits | iOS/Android/React Native | High | Interactive elements must expose correct roles/traits | Use accessibilityRole/button/link/checkbox etc. | Rely on generic views with no roles |
| Accessibility | Dynamic Updates | iOS/Android/React Native | Medium | Async status updates should be announced to screen readers | Use accessibilityLiveRegion or announceForAccessibility | Update text silently with no announcement |
| Accessibility | Decorative Icons | iOS/Android/React Native | Medium | Decorative icons should be hidden from screen readers | Mark decorative icons as not accessible | Have screen reader read every icon |
| Touch | Touch Target Size | iOS/Android/React Native | Critical | Primary touch targets must be at least 44x44pt | Increase hitSlop or padding to meet minimum | Small icons with tiny touch area |
| Touch | Touch Spacing | iOS/Android/React Native | Medium | Adjacent touch targets need enough spacing | Keep at least 8dp spacing between touchables | Cluster many buttons with no gap |
| Touch | Gesture Conflicts | iOS/Android/React Native | High | Custom gestures must not break system scroll/back | Reserve horizontal swipes for carousels | Full-screen custom swipe conflicting with back |
| Navigation | Back Behavior | iOS/Android/React Native | Critical | Back navigation should be predictable and preserve state | Use navigation.goBack and keep screen state | Reset stack or exit app unexpectedly |
| Navigation | Bottom Tabs | iOS/Android/React Native | Medium | Bottom tab bar should have at most 5 primary items | Use 3–5 tabs and move extras to More/Settings | Overloaded tab bar with many icons |
| Navigation | Modal Escape | iOS/Android/React Native | High | Modals/sheets must have clear close actions | Provide close button and swipe-down where platform expects | Trapping users in modal with no obvious exit |
| State | Preserve Screen State | iOS/Android/React Native | Medium | Returning to a screen should restore its scroll and form state | Keep components mounted or persist state | Reset list scroll and form inputs on every visit |
| Feedback | Loading Indicators | iOS/Android/React Native | High | Show visible feedback during network operations | Use ActivityIndicator or skeleton for >300ms operations | Leave button and screen frozen |
| Feedback | Success Feedback | iOS/Android/React Native | Medium | Confirm successful actions with brief feedback | Show toast/checkmark or banner | Complete actions silently with no confirmation |
| Feedback | Error Feedback | iOS/Android/React Native | High | Show clear error messages near the problem | input-level error + summary banner | Only change border color with no explanation |
| Forms | Inline Validation | iOS/Android/React Native | Medium | Validate inputs on blur or submit with clear messaging | Validate onBlur and onSubmit | Validate on every keystroke causing jank |
| Forms | Keyboard Type | iOS/Android/React Native | Medium | Use appropriate keyboardType and returnKeyType | Match email/tel/number/search types | Use default keyboard for all inputs |
| Forms | Auto Focus & Next | iOS/Android/React Native | Low | Guide users through form fields with Next/Done flows | Use onSubmitEditing to focus next input | Force users to tap each field manually |
| Forms | Password Visibility | iOS/Android/React Native | Medium | Allow toggling password visibility securely | Provide Show/Hide icon toggling secureTextEntry | Force users to type blind with no option |
| Performance | Virtualize Long Lists | iOS/Android/React Native | High | Use FlatList/SectionList for lists over ~50 items | Use keyExtractor and initialNumToRender appropriately | Render hundreds of items with ScrollView |
| Performance | Image Size & Cache | iOS/Android/React Native | Medium | Use correctly sized and cached images | Use Image component with proper resizeMode and caching | Load full-resolution images everywhere |
| Performance | Debounce High-Freq Events | iOS/Android/React Native | Medium | Debounce scroll/search callbacks to avoid jank | Wrap handlers with debounce/throttle | Run heavy logic on every event |
| Animation | Duration & Easing | iOS/Android/React Native | Medium | Micro-interactions should be 150–300ms with native-like easing | Use ease-out for enter/ease-in for exit | Use long or linear animations for core UI |
| Animation | Respect Reduced Motion | iOS/Android/React Native | Critical | Respect OS reduced-motion accessibility setting | Check reduceMotionEnabled and simplify animations | Ignore user motion preferences |
| Animation | Limited Continuous Motion | iOS/Android/React Native | Medium | Reserve infinite animations for loaders and live data | Use looping only where necessary | Keep decorative elements looping forever |
| Typography | Base Font Size | iOS/Android/React Native | High | Body text must be readable and support Dynamic Type | Use platform fontScale and at least 14–16pt base | Render critical text below 12pt |
| Typography | Dynamic Type Support | iOS/Android/React Native | High | Support system text scaling without breaking layout | Set allowFontScaling and test large text | Disable scaling on all text globally |
| Safe Areas | Safe Area Insets | iOS/Android/React Native | High | Content must not overlap notches/gesture bars | Wrap screens in SafeAreaView or apply insets | Place tappable content under system bars |
| Theming | Light/Dark Contrast | iOS/Android/React Native | High | Ensure sufficient contrast in both light and dark themes | Use semantic tokens and test both themes | Reuse light-theme grays directly in dark mode |
| Anti-Pattern | No Gesture-Only Actions | iOS/Android/React Native | Critical | Don't rely solely on hidden gestures for core actions | Provide visible buttons in addition to gestures | Rely on swipe/shake only with no UI affordance |
