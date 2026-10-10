# Store app — prototype

A standalone, iPad-first prototype for the people working the counter: recipes, roster, inventory, and sales — all in the same forest/cream/gold brand as the website and recipe book. **Nothing here is wired to a real backend.** It's one HTML page plus one JS file holding fabricated data in memory; every "save" just updates that in-memory data and re-renders, so it looks and feels real for a demo but resets on reload. That was the brief: a prototype, not a working system.

Open `index.html` directly, or serve the `website/` folder and visit `/ipad/`. The sign-in screen is `admin` / `2501`.

## What's in it

- **Sign-in** — a single shared passcode (`admin` / `2501`) unlocks the iPad, like a till login rather than a personal account; individual staff then pick their own name from "Signed in as" in the sidebar to personalise the roster. "Lock iPad" in that same menu re-locks it. The unlock is remembered for the browser tab's session (`sessionStorage`) so a reload mid-demo doesn't boot you back to the sign-in screen, but it's gone once the tab closes — there's no real auth behind it.
- **Overview** — today's takings, who's on shift right now, low-stock alerts, recent deliveries.
- **Recipes** — all 42 real flavours (from the recipe book), searchable and filterable by category, each opening into a full-page view (not a dismissable overlay — there's no backdrop to tap, so a messy hand mid-recipe can't close it by accident; the only way out is the labelled "Close recipe" button) with the story, "the point of this flavour," a sticky ingredients list beside the photo, and a tappable method checklist.
- **Roster** — a full year (2026) of scheduled shifts, browsable as a Week grid (tap an open shift to sign up as whichever staff member is "signed in"), a Month calendar (staffing dots per day, click through to that week), or a Year view (a heat-map card per month with scheduled hours and open-shift counts). Prev/Today/Next navigate within whichever mode is active; "Hours" and "shifts worked" below the grid track whatever period is on screen.
- **Inventory** — stock levels with low-stock flags, a recent-deliveries log, and a "Receive" shortcut or a full "Log a delivery" form that actually bumps the displayed stock number.
- **Budgets & Sales** — day/week/month totals, an animated trend chart (daily/weekly/monthly views), a top-flavours leaderboard, a comparison against the business plan's own £92,000/year (≈£7,667/month) objective, and a full transaction log (click "Avg transaction") browsable by Today / last 7 / last 14 days, with time, items, payment method and amount per sale.

## Design approach

Built to match the interaction quality of the reference prototype you shared (RELAY) — sliding-pill sidebar navigation, blurred/scaled page transitions, glass cards, animated counters and bar fills, a hand-drawn SVG trend chart with hover tooltips — but re-skinned entirely in Bean & Bear's own brand (Cormorant Garamond + Source Sans, forest/cream/gold, the walking-bear mark) rather than copying RELAY's own styling or content, which belonged to an unrelated teaching-analytics tool.

Responsive down to iPad portrait width (the sidebar becomes a bottom tab bar below ~860px) and up to a full desktop/Mac browser window: page content caps at a comfortable reading width and is centred rather than stretching edge-to-edge on a wide display, with roomier padding throughout than the original iPad-only pass.

The sidebar brand mark uses the real logo artwork (`website/assets/logo/`) — the vector bear icon and the "BEAN AND BEAR" wordmark, cropped from the official lockup files and stripped to transparent so they sit directly on the sidebar's gradient. No CSS-rendered logotype.

## The fabricated data

- **Recipes** are real — pulled from `BeanAndBear_RecipeBook.pdf`.
- **Staff, roster, inventory stock levels, deliveries, and sales figures** are invented for the demo, sized to roughly match the business plan's own numbers (e.g. sales in the same ballpark as the plan's 100 transactions/day case) so the screens feel proportionate rather than arbitrary. None of it is real trading data. The roster's one real-feeling week (12–18 Oct 2026) is hand-placed; the rest of the year is filled in by a seeded, deterministic generator so Month and Year browsing always has something plausible to show.
- **Transactions** are generated per day for the last 14 days of the sales chart, and each day's invented sales add up exactly to that day's figure already shown on the trend chart — so the two screens never visibly disagree. Items are drawn from the real 42 flavours (weighted toward the top-flavours leaderboard), at a handful of realistic price points (single/double/triple scoop, 500ml/1L tub, affogato), with a plausible spread of times-of-day and payment methods.

## Turning this into something real

Nothing here persists, and the sign-in is a hardcoded passcode check in client-side JS, not real authentication — fine for a demo, not for a till that handles real stock and sales. If this direction is the one to build on, the next step is a real backend — likely extending the PHP + SQLite system already in `website/admin/`, which has working login, recipes, inventory, time logs and financials, just with an older, plainer interface. This prototype could replace that interface once wired to real data and real auth; the two aren't connected today.
