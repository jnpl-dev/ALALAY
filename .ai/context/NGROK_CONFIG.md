# ALALAY: Ngrok Configuration
**How to serve ALALAY via ngrok without white screen / missing assets**

---

## The Problem

Accessing ALALAY via ngrok shows a white screen on mobile because the HTML loads Vite dev server assets from `localhost:5173` (which the phone can't reach).

## Root Cause

Running `npm run dev` creates `public/hot` containing the Vite dev server URL. Laravel's `@vite()` Blade directive checks for this file — if it exists, it injects dev server `<script>` tags instead of the production build.

## Workflow Options

### A. Production only (ngrok testing)

```
npm run build          # generate production assets in public/build/
php artisan serve      # start Laravel on port 8000
ngrok start            # tunnel port 8000
```

- `public/hot` must NOT exist
- Works every time

### B. Mixed (local dev + occasional ngrok)

```
npm run dev            # starts Vite dev server, creates public/hot
                       # ... do local development ...
npm run build          # rebuild production assets
```

Then **before accessing via ngrok**:

```
rm public/hot          # delete Vite dev server marker
                       # Access via ngrok — production assets served
```

To go back to local development:

```
npm run dev            # recreates public/hot, dev mode back
```

## Important

- If `npm run dev` is running and you delete `public/hot`, Vite will **recreate it immediately**
- To test via ngrok, **stop `npm run dev`** first, then `npm run build`, then access via ngrok
- `npm run build` does NOT create `public/hot` — it only writes to `public/build/`

## One-liner before ngrok (if dev server was running)

```bash
taskkill //F //IM node.exe 2>nul && rm public/hot && npm run build
```

---

## `.env` Settings

```env
APP_URL=https://your-ngrok-url.ngrok-free.dev
```

Update `APP_URL` to match your current ngrok URL so that signed URLs and asset paths generate correctly through the tunnel. No colon at the end.
