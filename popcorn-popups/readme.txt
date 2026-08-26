=== Popcorn Popups ===
Contributors: you
Tags: popup, modal, popups, lightbox, call to action
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A ridiculously fun popup builder. Pop things up on pages and posts with wild triggers, silly animations, and actual confetti.

== Description ==

Popcorn Popups adds a **Popups** menu to wp-admin where each popup is its own little post: write the content in the normal editor, then set it up with a tabbed builder.

**Triggers**

* ⏰ After a delay
* 📜 On scroll depth
* 🚪 Exit intent (with a flick-up fallback on phones, which have no cursor to lose)
* 👆 On click of any CSS selector
* 😴 When the visitor goes idle
* 🎲 Chaos mode — random timing, random position, random entrance, every page load

**Design**

Eight positions (center, top/bottom bar, all four corners, full screen), six entrances (pop, slide, fly, flip, jelly wobble, drop), your own colours, corner roundness, optional dimmed and blurred backdrop.

Borders are yours to set: none, solid, dashed, dotted or double, with your own thickness and colour. Drop shadow is a separate control — none, soft, medium or dramatic.

Two frames:

* **Card** — background, padding, rounded corners, shadow. The normal look.
* **Bare** — fully transparent. No background, border, shadow, margin or padding: just your content and the ✕. Made for dropping in an image, a video embed, or your own fully styled blocks.

**The fun bits**

* 🎉 Confetti — fire it the moment the popup pops, when someone clicks the button, or both. Four styles: corner cannons, center burst, fireworks, and confetti rain. Six colour palettes plus a custom four-colour picker.
* 🔊 A little synthesised "pop" on open — no audio file needed
* 🌧️ Emoji rain: type `🍿🎉✨` into one field and it rains

**Hello Bar**

One global, full-width announcement bar across the whole site, set up under **Popups → 👋 Hello Bar**. Emoji, message with basic HTML, button label and link, top or bottom, stuck to the window or sitting in the page flow, four colours, and an option to push the page down so it never covers your header. Visitors can close it, and it stays closed for as many days as you choose. Edit the message and it comes back for everyone, including people who dismissed the old one.

**Targeting — pages and posts**

* Everywhere
* Front page only
* All pages
* All posts
* Archives, blog index and search
* Hand-picked pages and posts, searched and chosen from a picker
* Or every post in given categories / tags (by slug)
* Plus an exclusion list that always wins

Layered on top: desktop vs mobile, logged-in vs logged-out, a start and end date, and a priority number so two popups never fight over the same page.

**Frequency capping (cookies)**

Pacing: every page view, once per session, once every X days, or once ever.

On top of that, **"stop after this many pops per visitor"** is a lifetime cap. Set it to 3 and a visitor sees the popup on three site visits and never again, whatever the pacing says. Set it to 0 for no cap.

All of it is counted in first-party cookies on the visitor's own device:

* `pcp_<id>_<stamp>` — how many times they have seen it and when they last did
* `pcp_s_<id>_<stamp>` — a session cookie for "once per session"
* `pcp_x_<id>_<stamp>` — set when they click your "Maybe later" link
* `pcp_hello_<hash>` — set when they close the Hello Bar

The `<stamp>` is a short hash of the popup's frequency settings. Change how often a popup should show and the old cookies stop counting, so a stale "seen it" or "no thanks" can never outlive the rule that created it. Editing copy or colours deliberately does not reset anyone's count.

"Every single page view" with no lifetime cap writes no cookie at all — there is nothing to count, so nothing is stored.

**Remember visitors for (days)** controls how long those cookies live. When they expire the visitor is treated as brand new.

Testing a popup and it will not show again? Hit **🍪 Forget me on this device** in the Test Drive box on the popup edit screen.

**Stats**

Each popup counts pops, button clicks and dismissals, and works out a click rate. There is a Scoreboard page under the Popups menu. Views from users who can edit posts are not counted, so your own testing does not pollute the numbers.

== Usage ==

1. Activate the plugin.
2. Go to **Popups → Pop a new one**.
3. Write the popup content in the editor. Blocks and shortcodes both work.
4. Work through the four builder tabs: Trigger, Design, Button, Where.
5. Hit **🍿 Pop it!** in the sidebar to preview it right there in the admin — no saving needed.
6. Publish.

**Shortcode**

Put an open-this-popup button anywhere in a page or post:

`[popcorn id="12" text="Show me the deal"]`

Or turn any existing element into a trigger by adding the attribute `data-popcorn-open="12"`, or by choosing the "On click" trigger and giving it a CSS selector.

== For developers ==

JavaScript API:

`Popcorn.open( 12 )`
`Popcorn.close( 12 )`
`Popcorn.reset( 12 )` — clear this visitor's cookies for one popup, or all of them with no argument
`PopcornHello.reset()` — bring the Hello Bar back on this device

Events fired on the popup element, and bubbling to `document`:

`popcorn:open`  — `detail: { id }`
`popcorn:close` — `detail: { id, reason }`

PHP filters:

* `popcorn_field_schema` — add or change builder fields
* `popcorn_matching_popups` — final say on which popups load
* `popcorn_popup_qualifies` — per-popup targeting override
* `popcorn_popup_config` — tweak the JSON handed to the front end
* `popcorn_popup_content` — filter the rendered popup body
* `popcorn_confetti_colors` — change the confetti palette in code
* `popcorn_hellobar_visible` — decide per request whether the Hello Bar shows

PHP action:

* `popcorn_tracked` — fires with `( $event, $popup_id, $new_total )`

== Notes and limits ==

* Device targeting is decided in the browser rather than on the server, so a full-page cache can never serve a desktop-only popup to a phone.
* Frequency capping and the visitor cap use first-party cookies, so they are per-device and per-browser. Clearing cookies resets the count, and a visitor who blocks cookies sees the popup on every visit.
* Those cookies are functional rather than tracking cookies — nothing is sent anywhere and no visitor is identified — but if your site shows a cookie notice, this is the sort of thing it should mention.
* The Hello Bar is printed hidden and revealed by JavaScript, so people who dismissed it never see it flash. That also means it does not appear at all with JavaScript disabled.
* The tracking endpoint is public and unauthenticated (it has to work for logged-out visitors). The counters are a rough popularity gauge, not audited analytics — treat them accordingly.
* One automatic popup shows per page view. Click-triggered popups are exempt, since the visitor asked for those.
* Respects `prefers-reduced-motion`: animations become a simple fade and the confetti and emoji rain sit it out.

== Changelog ==

= 1.3.0 =
* New border controls: none, solid, dashed, dotted or double, with your own thickness and colour.
* New drop shadow control: none, soft, medium or dramatic.
* Fixed: a faint hairline was baked into every popup's shadow, so a card always looked like it had a 1px border you could not turn off. Gone — borders are now entirely your call.
* Fixed: themes that style `button:hover` with a border were bleeding into the popup's close and call-to-action buttons, making a border appear on rollover. The buttons now hold their own styling through hover, focus and active.
* Fixed: images inside popup content no longer pick up theme hover borders.
* The scrollbar on a popup taller than the window is now styled deliberately rather than fading in as a stray native line.
* The popup box no longer paints a focus ring around itself when it takes focus on open. Keyboard users still get a clear ring on the buttons.

= 1.2.0 =
* Fixed: a popup set to "every single page view" could stop showing for good. Cookie names are now stamped with the popup's frequency settings, so an old "seen it" or "no thanks" cookie can no longer outlive the setting that created it.
* "Every single page view" with no lifetime cap now writes no cookie at all.
* New **Bare** frame: transparent background, no border, shadow, margin or padding — just your content and the ✕.
* New **🍪 Forget me on this device** button on the popup edit screen for clearing your own cookies while testing.

= 1.1.0 =
* Confetti can now fire the moment a popup opens, on the button click, or both.
* Four confetti styles: corner cannons, center burst, fireworks, confetti rain.
* Confetti colour palettes, including a custom four-colour picker.
* Frequency capping moved to first-party cookies.
* New per-visitor lifetime cap: stop after N pops, with a configurable cookie lifetime.
* New "distance from the corner" control for the four corner positions.
* New global Hello Bar: full-width top or bottom announcement bar, fully configurable.
* `Popcorn.reset()` and `PopcornHello.reset()` for clearing cookies while testing.

= 1.0.0 =
* First batch out of the pan. 🍿
