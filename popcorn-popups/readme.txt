=== Popcorn Popups ===
Contributors: you
Tags: popup, modal, popups, lightbox, call to action
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A ridiculously fun popup builder. Pop things up on pages and posts with wild triggers, silly animations, and actual confetti.

== Description ==

Popcorn Popups adds a **Popcorn** menu to wp-admin where each popup is its own little post: write the content in the normal editor, then set it up with a tabbed builder.

**Triggers**

* ⏰ After a delay
* 📜 On scroll depth
* 🚪 Exit intent (with a flick-up fallback on phones, which have no cursor to lose)
* 👆 On click of any CSS selector
* 😴 When the visitor goes idle
* 🎲 Chaos mode — random timing, random position, random entrance, every page load

**Design**

Eight positions (center, top/bottom bar, all four corners, full screen), six entrances (pop, slide, fly, flip, jelly wobble, drop), your own colours, corner roundness, optional dimmed and blurred backdrop.

**The fun bits**

* 🎉 Confetti when someone clicks the button
* 🔊 A little synthesised "pop" on open — no audio file needed
* 🌧️ Emoji rain: type `🍿🎉✨` into one field and it rains

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

**Frequency capping**

Every page view, once per session, once every X days, or once ever. Add a "Maybe later" link and that visitor never sees it again on that device.

**Stats**

Each popup counts pops, button clicks and dismissals, and works out a click rate. There is a Scoreboard page under the Popcorn menu. Views from users who can edit posts are not counted, so your own testing does not pollute the numbers.

== Usage ==

1. Activate the plugin.
2. Go to **Popcorn → Pop a new one**.
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

Events fired on the popup element, and bubbling to `document`:

`popcorn:open`  — `detail: { id }`
`popcorn:close` — `detail: { id, reason }`

PHP filters:

* `popcorn_field_schema` — add or change builder fields
* `popcorn_matching_popups` — final say on which popups load
* `popcorn_popup_qualifies` — per-popup targeting override
* `popcorn_popup_config` — tweak the JSON handed to the front end
* `popcorn_popup_content` — filter the rendered popup body

PHP action:

* `popcorn_tracked` — fires with `( $event, $popup_id, $new_total )`

== Notes and limits ==

* Device targeting is decided in the browser rather than on the server, so a full-page cache can never serve a desktop-only popup to a phone.
* Frequency capping uses `localStorage` / `sessionStorage`, so it is per-device and per-browser, and clearing site data resets it.
* The tracking endpoint is public and unauthenticated (it has to work for logged-out visitors). The counters are a rough popularity gauge, not audited analytics — treat them accordingly.
* One automatic popup shows per page view. Click-triggered popups are exempt, since the visitor asked for those.
* Respects `prefers-reduced-motion`: animations become a simple fade and the confetti and emoji rain sit it out.

== Changelog ==

= 1.0.0 =
* First batch out of the pan. 🍿
