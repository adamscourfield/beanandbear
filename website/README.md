# Bean and Bear: website (rebuilt from the brand book)

Vanilla HTML and CSS. No JavaScript, no build step. Open index.html;
bean-and-bear-website.html is the same site as one file.

## The page

Two pages, switched by the bear icon (top left, always home) and a
\"Shop all 42 flavours\" link.

**Home** --- fast to the gelato: a full-screen photo (the sign and
tagline over it, no blank space, no scroll prompt), the brand story, the
family story, a photo of the counter, then ten flavours in a horizontal
scrolling strip, delivery (postcode check, map, the take-home tub) and
the opening notice. Non-essential copy has been cut throughout; only the
family story keeps its full text.

**Shop** --- every flavour: the full 42, with six filters. Each card
leads with the photograph; tap one to open it, with its story, \"the
idea\", what it\'s made with, and only then the tub and the price.

Account (Google, Apple, Facebook, email --- all mock) and the basket are
reachable from any page.

## Design

From the brand book: forest #0F2D23, warm cream #F7F1E6, antique gold
#C9A05F; Cormorant Garamond and Source Sans 3 (self-hosted);
tracked-caps labels, gold hairline frames, italic gold subtitles, cream
flavour cards on a brass plate. The marble matches the covers of the
printed documents. Phone first; the same page opens up on a laptop
(four-column gelato, two-column flavour pages, basket as a drawer).

## How the sheets work

The account, basket and each flavour open as sheets. They are \<label\>s
for hidden radio inputs, with no #anchor links, so they also work inside
sandboxed previews. Nothing moves on scroll. prefers-reduced-motion is
respected.

## Photographs

assets/img/hero-store.jpg is a cleaned copy of the store photograph (the
fascia lettering and the window price board painted out), so the logo on
the page is the sign. See PHOTO_FIXES.md for three shop photographs that
need regenerating, and PHOTO_BRIEF.md for the flavour photographs and
card text.

## Before this goes live

-   Define real delivery terms: area, fees, slots, cold-chain packing
    > and courier.

-   Replace the social buttons with each provider\'s official buttons
    > and SDK.

-   Add allergen information per flavour; confirm VAT display.

-   Menus and buttons are labels for radio inputs: right for a
    > prototype, but not keyboard or screen-reader friendly. The live
    > build should use real links and buttons.
