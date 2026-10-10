# Store app — prototype

A standalone, iPad-first prototype for the people working the counter: recipes, roster, inventory, and sales — all in the same forest/cream/gold brand as the website and recipe book. **Nothing here is wired to a real backend.** It's one HTML page plus one JS file holding fabricated data in memory; every "save" just updates that in-memory data and re-renders, so it looks and feels real for a demo but resets on reload. That was the brief: a prototype, not a working system.

Open `index.html` directly, or serve the `website/` folder and visit `/ipad/`.

## What's in it

- **Overview** — today's takings, who's on shift right now, low-stock alerts, recent deliveries.
- **Recipes** — all 42 real flavours (from the recipe book), searchable and filterable by category, each opening into a detail panel with the story, "the point of this flavour," ingredients, and a tappable method checklist.
- **Roster** — a real week, open shifts you can tap to sign up for (as whichever staff member is "signed in," switchable from the sidebar), hours-this-week per person, and a worked-shifts history.
- **Inventory** — stock levels with low-stock flags, a recent-deliveries log, and a "Receive" shortcut or a full "Log a delivery" form that actually bumps the displayed stock number.
- **Budgets & Sales** — day/week/month totals, an animated trend chart (daily/weekly/monthly views), a top-flavours leaderboard, and a comparison against the business plan's own £92,000/year (≈£7,667/month) objective.

## Design approach

Built to match the interaction quality of the reference prototype you shared (RELAY) — sliding-pill sidebar navigation, blurred/scaled page transitions, glass cards, animated counters and bar fills, a hand-drawn SVG trend chart with hover tooltips — but re-skinned entirely in Bean & Bear's own brand (Cormorant Garamond + Source Sans, forest/cream/gold, the walking-bear mark) rather than copying RELAY's own styling or content, which belonged to an unrelated teaching-analytics tool.

Responsive down to iPad portrait width: the sidebar becomes a bottom tab bar below ~860px.

The sidebar brand mark uses the real logo artwork (`website/assets/logo/`) — the vector bear icon and the "BEAN AND BEAR" wordmark, cropped from the official lockup files and stripped to transparent so they sit directly on the sidebar's gradient. No CSS-rendered logotype.

## The fabricated data

- **Recipes** are real — pulled from `BeanAndBear_RecipeBook.pdf`.
- **Staff, roster, inventory stock levels, deliveries, and sales figures** are invented for the demo, sized to roughly match the business plan's own numbers (e.g. sales in the same ballpark as the plan's 100 transactions/day case) so the screens feel proportionate rather than arbitrary. None of it is real trading data.

## Turning this into something real

Nothing here persists or authenticates. If this direction is the one to build on, the next step is a real backend — likely extending the PHP + SQLite system already in `website/admin/`, which has working login, recipes, inventory, time logs and financials, just with an older, plainer interface. This prototype could replace that interface once wired to real data and real auth; the two aren't connected today.
