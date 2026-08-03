# Source photography credits

Every storefront image is built from the photographs in this folder by
`tools/build_images.php`. Read this before going to production.

## Licences

The source frames are **not committed to git** (~20 MB). Run
`tools/fetch_sources.php` to pull them; it holds the same ids listed here.

| Source file | Origin | Reference | Licence | Attribution |
|---|---|---|---|---|
| `flatlay.jpg` | Unsplash | `photo-1596040033229-a9821ebd058d` | [Unsplash](https://unsplash.com/license) | Not required |
| `oilbottle.jpg` | Unsplash | `photo-1474979266404-7eaacbcd87c5` | Unsplash | Not required |
| `market.jpg` | Unsplash | `photo-1532336414038-cf19250c5757` | Unsplash | Not required |
| `coconut.jpg` | Unsplash | `photo-1580984969071-a8da5656c2fb` | Unsplash | Not required |
| `herbs.jpg` | Unsplash | `photo-1615485500704-8e990f9900f7` | Unsplash | Not required |
| `spoons.jpg` | Unsplash | `photo-1509358271058-acd22cc93898` | Unsplash | Not required |
| `chillibowl.jpg` | Unsplash | `photo-1621939514649-280e2ee25f60` | Unsplash | Not required |
| `cardamom.jpg` | WordPress Photo Directory | [photo 8669274741](https://wordpress.org/photos/photo/8669274741/) by Bigul Malayi | CC0 | Not required |

The Unsplash Licence grants free commercial use without permission or
attribution. It does **not** permit selling unmodified copies of the photos
themselves, or building a competing stock-photo service — neither applies to a
storefront. CC0 waives all rights.

`herbs.jpg`, `spoons.jpg` and `chillibowl.jpg` are fetched but not currently
used by any recipe; they are kept as alternates.

The Unsplash Licence grants free commercial use without permission or
attribution. It does **not** permit selling unmodified copies of the photos
themselves, or building a competing stock-photo service — neither applies to a
storefront. CC0 waives all rights.

## What these images are, and are not

They are **stock photographs of the correct ingredients**, not photographs of
Kupiana's actual products. That is fine for launch and for a demo, but before
you print packaging or run paid ads, replace them with a real shoot: a customer
comparing the product tile to what arrives in the box should see the same thing.

## Where each product image comes from

Eight of the ten product images are cropped from a **single flatlay
photograph** (`flatlay.jpg`, 6000×4000). This is deliberate — one shoot means
identical lighting, white balance and background across the whole product grid,
which is what makes a catalogue look art-directed instead of assembled from
mismatched stock.

| Product | Source |
|---|---|
| Organic Lakadong Turmeric Powder | `flatlay.jpg` — turmeric mound |
| Malabar Black Pepper Whole | `flatlay.jpg` — peppercorns |
| Kashmiri Chilli Powder | `flatlay.jpg` — chilli powder |
| Organic Garam Masala Blend | `flatlay.jpg` — ground masala |
| Organic Cumin Seeds | `flatlay.jpg` — cumin seeds |
| Ginger Garlic Spice Mix | `flatlay.jpg` — terracotta bowl |
| Green Cardamom Pods | `cardamom.jpg` |
| Virgin Coconut Oil | `coconut.jpg` — halved coconuts |
| Cold-Pressed Groundnut Oil | `oilbottle.jpg` — wide crop |
| Wood-Pressed Sesame Oil | `oilbottle.jpg` — tight crop |

## Known gap

**Groundnut oil and sesame oil share one bottle photograph**, cropped wide and
tight. They are both clear golden pressed oils so it reads acceptably, but a
sharp-eyed visitor will spot the same bottle. These two are the first images to
replace with real product shots.

## Replacing any image with your own

1. Drop your photo into this folder.
2. Point the recipe's `src` at it in `tools/build_images.php`, and either set a
   `crop` (centre X, centre Y, width, height — in *source* pixels) or
   `'cover' => TRUE` to centre-crop the whole frame.
3. Run:
   ```bash
   /Applications/XAMPP/bin/php tools/build_images.php --sheet
   ```

The output overwrites the exact filenames the database already points at, so
no SQL changes are needed. The `--sheet` flag writes a contact sheet to
`/tmp/kupiana_images.jpg` so you can check the whole set at a glance.
