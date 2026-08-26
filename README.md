# 🍿 Popcorn Popups

A ridiculously fun popup builder for WordPress. Pop things up on pages and posts with wild triggers, silly animations, and actual confetti.

Each popup is its own little post: write the content in the normal editor, then set it up with a tabbed builder.

## Features

### Triggers

| | Trigger | Notes |
|---|---|---|
| ⏰ | After a delay | Wait *n* seconds, then pop |
| 📜 | On scroll | Fires at a chosen scroll depth |
| 🚪 | Exit intent | Cursor leaves the viewport, with a flick-up fallback on phones |
| 👆 | On click | Any CSS selector you name becomes a trigger |
| 😴 | When idle | No mouse, keys or scrolling for *n* seconds |
| 🎲 | Chaos mode | Random timing, position and entrance on every page load |

### Design

Eight positions (center, top bar, bottom bar, all four corners, full screen), six entrances (pop, slide, fly, flip, jelly wobble, drop), your own colours, corner roundness, and an optional dimmed and blurred backdrop. Corner popups get a "distance from the corner" control.

**Borders** are yours to set — none, solid, dashed, dotted or double, with your own thickness and colour. **Drop shadow** is a separate control: none, soft, medium or dramatic.

Two frames:

| | Frame | What you get |
|---|---|---|
| 🗂️ | Card | Background, padding, rounded corners, shadow — the normal look |
| 👻 | Bare | Fully transparent. No background, border, shadow, margin or padding: just your content and the ✕ |

**Bare** is for dropping in an image, a video embed, or your own fully styled blocks with nothing framing them.

### The fun bits

- 🎉 **Confetti**, fired the moment the popup pops, on the button click, or both
- 🔊 A synthesised "pop" on open — WebAudio, no audio file needed
- 🌧️ Emoji rain: type `🍿🎉✨` into one field and it rains

Four confetti styles:

| | Style | What it does |
|---|---|---|
| 🎊 | Corner cannons | Fires in from both bottom corners, filling the window around the popup |
| 💥 | Center burst | One big bang, middle out |
| 🎆 | Fireworks | Several pops in sequence across the window |
| 🌧️ | Confetti rain | Drifts down the whole page |

Six colour palettes — Popcorn, Party, Neon, Gold, Monochrome, or just your accent colour — plus a custom four-colour picker.

All of it stands down under `prefers-reduced-motion`.

### 👋 Hello Bar

One global, full-width announcement bar across the whole site, under **Popups → 👋 Hello Bar**.

- Top or bottom of the window
- Stuck to the window while scrolling, or sitting in the normal page flow
- Emoji, message (basic HTML allowed), button label and link, new tab or not
- Four colours: bar background, bar text, button background, button text
- Pushes the page down so it never covers your header
- Visitors can close it; it stays closed for as many days as you choose
- Editing the message brings it back for everyone, including people who dismissed the old one
- Live preview right on the settings screen

### Targeting — pages and posts

Everywhere · front page only · all pages · all posts · archives, blog index and search · hand-picked pages and posts via a search-and-chip picker · or every post in given categories and tags.

Layered on top: an exclusion list that always wins, desktop vs mobile, logged-in vs logged-out, a start and end date, and a priority number so two popups never fight over the same page.

### Frequency capping — cookies

**Pacing:** every page view, once per session, once every *X* days, or once ever.

**Lifetime cap:** *stop after this many pops per visitor*. Set it to 3 and a visitor sees the popup on three site visits and never again, whatever the pacing says. `0` means no cap.

Counted in first-party cookies on the visitor's own device:

| Cookie | Holds |
|---|---|
| `pcp_<id>_<stamp>` | How many times they have seen it, and when they last did |
| `pcp_s_<id>_<stamp>` | Session marker for "once per session" |
| `pcp_x_<id>_<stamp>` | Set when they click your "Maybe later" link |
| `pcp_hello_<hash>` | Set when they close the Hello Bar |

`<stamp>` is a short hash of the popup's frequency settings. **Change how often a popup should show and the old cookies stop counting**, so a stale "seen it" or "no thanks" can never outlive the rule that created it. Editing copy or colours deliberately does not reset anyone's count.

"Every page view" with no lifetime cap writes no cookie at all — there is nothing to count, so nothing is stored.

*Remember visitors for (days)* controls how long those cookies live. When they expire, the visitor is treated as brand new.

Testing a popup and it won't show again? Hit **🍪 Forget me on this device** in the Test Drive box on the popup edit screen.

### Stats

Every popup counts pops, button clicks and dismissals, and works out a click rate. There is a Scoreboard page under the Popups menu. Views from users who can edit posts are not counted, so your own testing does not pollute the numbers.

## Install

Download or clone, then either:

- copy the `popcorn-popups/` folder into `wp-content/plugins/`, or
- zip that folder and upload it under **Plugins → Add New → Upload Plugin**.

Then activate and head to **Popups → Pop a new one**.

## Usage

1. Write the popup content in the editor. Blocks and shortcodes both work.
2. Work through the four builder tabs: **Trigger**, **Design**, **Button**, **Where**.
3. Hit **🍿 Pop it!** in the sidebar to preview it right there in the admin — no saving needed.
4. Publish.

### Shortcode

Put an open-this-popup button anywhere in a page or post:

```
[popcorn id="12" text="Show me the deal"]
```

Or turn any existing element into a trigger with `data-popcorn-open="12"`, or by choosing the **On click** trigger and giving it a CSS selector.

## For developers

JavaScript API:

```js
Popcorn.open( 12 );
Popcorn.close( 12 );
Popcorn.reset( 12 );   // clear this visitor's cookies for one popup
Popcorn.reset();       // ...or for every popup on the page
PopcornHello.reset();  // bring the Hello Bar back on this device
```

Events fire on the popup element and bubble to `document`:

```js
document.addEventListener( 'popcorn:open', ( e ) => console.log( e.detail.id ) );
document.addEventListener( 'popcorn:close', ( e ) => console.log( e.detail.reason ) );
```

PHP filters:

| Filter | Purpose |
|---|---|
| `popcorn_field_schema` | Add or change builder fields |
| `popcorn_matching_popups` | Final say on which popups load |
| `popcorn_popup_qualifies` | Per-popup targeting override |
| `popcorn_popup_config` | Tweak the JSON handed to the front end |
| `popcorn_popup_content` | Filter the rendered popup body |
| `popcorn_confetti_colors` | Change the confetti palette in code |
| `popcorn_hellobar_visible` | Decide per request whether the Hello Bar shows |

PHP action: `popcorn_tracked`, fired with `( $event, $popup_id, $new_total )`.

## Notes and limits

- Device targeting is decided in the browser rather than on the server, so a full-page cache can never serve a desktop-only popup to a phone.
- Frequency capping and the visitor cap use first-party cookies, so they are per-device and per-browser. Clearing cookies resets the count, and a visitor who blocks cookies sees the popup on every visit.
- Those cookies are functional rather than tracking cookies — nothing is sent anywhere and no visitor is identified — but if your site shows a cookie notice, this is the sort of thing it should mention.
- The Hello Bar is printed hidden and revealed by JavaScript, so people who dismissed it never see it flash. That also means it does not appear at all with JavaScript disabled.
- The tracking endpoint is public and unauthenticated — it has to work for logged-out visitors. The counters are a rough popularity gauge, not audited analytics.
- One automatic popup shows per page view. Click-triggered popups are exempt, since the visitor asked for those.
- Uninstalling removes only the plugin's own option. Your popups are content, so they stay in the database.

## Requirements

WordPress 6.0+, PHP 7.4+.

## License

GPL-2.0-or-later.
