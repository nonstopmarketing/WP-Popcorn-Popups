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

Eight positions (center, top bar, bottom bar, all four corners, full screen), six entrances (pop, slide, fly, flip, jelly wobble, drop), your own colours, corner roundness, and an optional dimmed and blurred backdrop.

### The fun bits

- 🎉 Canvas confetti when someone clicks the button
- 🔊 A synthesised "pop" on open — WebAudio, no audio file needed
- 🌧️ Emoji rain: type `🍿🎉✨` into one field and it rains

All of it stands down under `prefers-reduced-motion`.

### Targeting — pages and posts

Everywhere · front page only · all pages · all posts · archives, blog index and search · hand-picked pages and posts via a search-and-chip picker · or every post in given categories and tags.

Layered on top: an exclusion list that always wins, desktop vs mobile, logged-in vs logged-out, a start and end date, and a priority number so two popups never fight over the same page.

### Frequency capping

Every page view, once per session, once every *X* days, or once ever. Add a "Maybe later" link and that visitor never sees it again on that device.

### Stats

Every popup counts pops, button clicks and dismissals, and works out a click rate. There is a Scoreboard page under the Popcorn menu. Views from users who can edit posts are not counted, so your own testing does not pollute the numbers.

## Install

Download or clone, then either:

- copy the `popcorn-popups/` folder into `wp-content/plugins/`, or
- zip that folder and upload it under **Plugins → Add New → Upload Plugin**.

Then activate and head to **Popcorn → Pop a new one**.

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

PHP action: `popcorn_tracked`, fired with `( $event, $popup_id, $new_total )`.

## Notes and limits

- Device targeting is decided in the browser rather than on the server, so a full-page cache can never serve a desktop-only popup to a phone.
- Frequency capping uses `localStorage` / `sessionStorage`, so it is per-device and per-browser, and clearing site data resets it.
- The tracking endpoint is public and unauthenticated — it has to work for logged-out visitors. The counters are a rough popularity gauge, not audited analytics.
- One automatic popup shows per page view. Click-triggered popups are exempt, since the visitor asked for those.
- Uninstalling removes only the plugin's own option. Your popups are content, so they stay in the database.

## Requirements

WordPress 6.0+, PHP 7.4+.

## License

GPL-2.0-or-later.
