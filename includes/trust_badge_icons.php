<?php
/**
 * DRIYUM - Curated Trust Badge Icon Library
 * Industry-standard, pixel-accurate vector SVGs and helpers for quality pledge trust badges.
 * Built with precision matching the reference brand assets.
 */

if (!function_exists('get_trust_badge_icon_library')) {
    function get_trust_badge_icon_library() {
        $library = [
            // ==============================================================
            // 1. NUMERALS 0 TO 6 (Cohesive Designer Monoline + Accent Checkmark)
            // ==============================================================
            'num-0' => [
                'label' => '0️⃣ Numeral 0 (Zero Preservatives / 0 Chemicals)',
                'default_line1' => 'ZERO',
                'default_line2' => 'PRESERVATIVES',
                'svg' => '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M32 12 C21 12 16 21 16 34 C16 47 21 56 32 56 C43 56 48 47 48 34 C48 21 43 12 32 12 Z" stroke="#2D231E" stroke-width="3" fill="none"/>
                    <circle cx="32" cy="34" r="9" stroke="#2D231E" stroke-width="2.4" fill="none"/>
                    <path d="M26 34 L31 39 L43 27" stroke="#00B259" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>'
            ],
            'num-1' => [
                'label' => '1️⃣ Numeral 1 (#1 Grade / 100% Pure)',
                'default_line1' => '#1 GRADE',
                'default_line2' => 'PREMIUM QUALITY',
                'svg' => '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22 20 L32 13 V53 M22 53 H42" stroke="#2D231E" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M37 25 L42 30 L52 20" stroke="#00B259" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>'
            ],
            'num-2' => [
                'label' => '2️⃣ Numeral 2 (2X Crunch / 2-Step Sort)',
                'default_line1' => '2X CRUNCH',
                'default_line2' => '& FRESHNESS',
                'svg' => '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 22 C20 15 25 12 32 12 C39.5 12 44 16 44 23 C44 32 35 39 19 53 H46" stroke="#2D231E" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M25 25 L29 29 L38 20" stroke="#00B259" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>'
            ],
            'num-3' => [
                'label' => '3️⃣ Numeral 3 (3-Stage Quality Check)',
                'default_line1' => '3 STAGE',
                'default_line2' => 'QUALITY CHECK',
                'svg' => '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M21 13 H43 L31 29 C38.5 29 44 33.5 44 41 C44 48 38 53 29 53 C22 53 17 49 16 44" stroke="#2D231E" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M24 41 L28 45 L37 36" stroke="#00B259" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>'
            ],
            'num-4' => [
                'label' => '4️⃣ Numeral 4 (4-Layer Inspection)',
                'default_line1' => '4 LAYER',
                'default_line2' => 'INSPECTION',
                'svg' => '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M38 11 L16 40 H45 M38 31 V53" stroke="#2D231E" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M24 33 L27.5 36.5 L34.5 28.5" stroke="#00B259" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>'
            ],
            'num-5' => [
                'label' => '5️⃣ Numeral 5 (5-Star Quality / 5 Steps)',
                'default_line1' => '5 STAR',
                'default_line2' => 'QUALITY RATED',
                'svg' => '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M42 13 H21 L19 28 C23 26 27 25 31.5 25 C39.5 25 45 29.5 45 38.5 C45 47.5 38.5 53 29 53 C22 53 17 49 16 44" stroke="#2D231E" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M24 39 L28 43 L37 34" stroke="#00B259" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>'
            ],
            'quality-check' => [
                'label' => '6️⃣ Numeral 6 (6 Layer Quality Check - Exact Reference)',
                'default_line1' => '6 LAYER',
                'default_line2' => 'QUALITY CHECK',
                'svg' => '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Stylized wedge-top numeral 6 with concentric loop matching reference photo -->
                    <path d="M46 16 L35 16 C25 24 20 33 20 42 C20 51 27 57 37 57 C47 57 53 50 53 40 C53 30 46 25 36 25 C31 25 26 27 22 31" stroke="#2D231E" stroke-width="3" stroke-linecap="round" fill="none"/>
                    <circle cx="37" cy="41" r="9" stroke="#2D231E" stroke-width="2.6" fill="none"/>
                    <!-- Accent Checkmark through loop center -->
                    <path d="M32 41 L37 46 L51 32" stroke="#00B259" stroke-width="3.6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>'
            ],
            'num-6' => [
                'label' => '6️⃣ Numeral 6 (Alias)',
                'default_line1' => '6 LAYER',
                'default_line2' => 'QUALITY CHECK',
                'svg' => '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M46 16 L35 16 C25 24 20 33 20 42 C20 51 27 57 37 57 C47 57 53 50 53 40 C53 30 46 25 36 25 C31 25 26 27 22 31" stroke="#2D231E" stroke-width="3" stroke-linecap="round" fill="none"/>
                    <circle cx="37" cy="41" r="9" stroke="#2D231E" stroke-width="2.6" fill="none"/>
                    <path d="M32 41 L37 46 L51 32" stroke="#00B259" stroke-width="3.6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>'
            ],

            // ==============================================================
            // 2. OFFICIAL FSSAI LOGO (Clean Typography + Signature Dual Swooshes)
            // ==============================================================
            'fssai' => [
                'label' => '🇮🇳 FSSAI Official Certified (Wordmark & Dynamic Swooshes)',
                'default_line1' => 'FSSAI',
                'default_line2' => 'CERTIFIED',
                'svg' => '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Clean Official fssai Typography -->
                    <!-- f -->
                    <path d="M15 16 C17 14 20 14 21 16 V34 M12 23 H24" stroke="#2D231E" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
                    <!-- first s -->
                    <path d="M29 20 C27 18 24 18 24 21 C24 24 30 23 30 27 C30 30.5 26.5 31 24 29.5" stroke="#2D231E" stroke-width="2.6" stroke-linecap="round"/>
                    <!-- second s -->
                    <path d="M38 20 C36 18 33 18 33 21 C33 24 39 23 39 27 C39 30.5 35.5 31 33 29.5" stroke="#2D231E" stroke-width="2.6" stroke-linecap="round"/>
                    <!-- a -->
                    <path d="M46 22 C43 19.5 40 21.5 40 25.5 C40 29.5 43 31 46 29 V20 M46 25 V31" stroke="#2D231E" stroke-width="2.6" stroke-linecap="round"/>
                    <!-- i -->
                    <path d="M51 21 V31 M51 16 V16.5" stroke="#2D231E" stroke-width="2.6" stroke-linecap="round"/>
                    <!-- Signature Dynamic Dual Swooshes (Saffron & Green) -->
                    <path d="M11 38 C23 43 43 43 55 38" stroke="#E85D26" stroke-width="2.8" stroke-linecap="round"/>
                    <path d="M13 42 C25 47 41 47 53 42" stroke="#00B259" stroke-width="3" stroke-linecap="round"/>
                </svg>'
            ],

            // ==============================================================
            // 3. ACCURATE INDIA MAP & SOURCING (Exact Reference Matching zoom_india.png)
            // ==============================================================
            'made-in-india' => [
                'label' => '🇮🇳 Made in India For India (Exact Geographical Silhouette + 3D Parcel)',
                'default_line1' => 'MADE IN INDIA',
                'default_line2' => 'FOR INDIA',
                'svg' => '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Accurate Geographic Silhouette of India matching reference zoom_india.png -->
                    <path d="M26 6 C27.5 4 29 4 30.5 5.5 C32 7 31.5 10 33.5 11 C31.5 13 28 14 26 17 C24 19 23 20 23.5 22 C20 22 17.5 23.5 16.5 25 C15.5 26.5 17 28 18.5 28.5 C16.5 29.5 17.5 32 19.5 32.5 C21.5 33 22 32 23 33 C23 36 24.5 40 26 44 C27.5 48 28.5 51 29.5 54 C30.5 51 32.5 47 34 43 C35.5 39 37.5 35 38 31 C39 28 41 27.5 42 25.5 C43.5 24.5 46.5 23 48.5 20 C49 18 47 17 45 17.5 C43 18 41 18.5 39.5 17 C38 15.5 36.5 14 35.5 12 C34 9.5 32.5 8 30.5 5.5" stroke="#2D231E" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                    <!-- 3D Parcel Delivery Box at South-East with Sealing Tape Strap -->
                    <path d="M36 36 L45 31 L54 36 L45 41 Z" stroke="#E85D26" stroke-width="2.6" stroke-linejoin="round" fill="#FAF8F5"/>
                    <path d="M36 36 V46 L45 51 V41 Z" stroke="#E85D26" stroke-width="2.6" stroke-linejoin="round" fill="#FAF8F5"/>
                    <path d="M54 36 V46 L45 51 V41 Z" stroke="#E85D26" stroke-width="2.6" stroke-linejoin="round" fill="#FAF8F5"/>
                    <path d="M40.5 33.5 L49.5 38.5" stroke="#E85D26" stroke-width="2.4" stroke-linecap="round"/>
                    <path d="M45 41 V51" stroke="#E85D26" stroke-width="2.6" stroke-linecap="round"/>
                </svg>'
            ],
            'kashmir' => [
                'label' => '🏔️ Direct From Kashmir (Mountain Peaks & Rising Sun - Reference)',
                'default_line1' => 'DIRECT FROM',
                'default_line2' => 'KASHMIR',
                'svg' => '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Rising Green Sun over Mountain Ridge -->
                    <circle cx="36" cy="18" r="5" stroke="#00B259" stroke-width="2.6" fill="none"/>
                    <path d="M36 10V8M41 12L43 10M43 18H45M41 24L43 26M31 12L29 10" stroke="#00B259" stroke-width="2.4" stroke-linecap="round"/>
                    <!-- Mountain Peaks with Flat Base -->
                    <path d="M12 48L24 24L33 36L44 18L56 48H12Z" stroke="#2D231E" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                </svg>'
            ],

            // ==============================================================
            // 4. ACCURATE LEAF & BOTANICAL ICONS (Matching reference media)
            // ==============================================================
            'organic' => [
                'label' => '🌿 100% Organic (Iconic Green Brand Leaf Emblem - Reference)',
                'default_line1' => '100% ORGANIC',
                'default_line2' => '& NATURAL',
                'svg' => '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Clean aerodynamic organic leaf emblem matching reference photo -->
                    <path d="M16 50 C14 32 26 16 48 14 C46 36 30 52 16 50 Z" fill="#00B259" stroke="#2D231E" stroke-width="2.4" stroke-linejoin="round"/>
                    <!-- Elegant central white stem slit -->
                    <path d="M16 50 C26 38 36 28 48 14" stroke="#FAF8F5" stroke-width="2.8" stroke-linecap="round"/>
                    <!-- Petiole base -->
                    <path d="M16 50 L11 55" stroke="#2D231E" stroke-width="2.8" stroke-linecap="round"/>
                </svg>'
            ],
            'leaf' => [
                'label' => '🌿 100% Organic (Alias)',
                'default_line1' => '100% ORGANIC',
                'default_line2' => '& NATURAL',
                'svg' => '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M16 50 C14 32 26 16 48 14 C46 36 30 52 16 50 Z" fill="#00B259" stroke="#2D231E" stroke-width="2.4" stroke-linejoin="round"/>
                    <path d="M16 50 C26 38 36 28 48 14" stroke="#FAF8F5" stroke-width="2.8" stroke-linecap="round"/>
                    <path d="M16 50 L11 55" stroke="#2D231E" stroke-width="2.8" stroke-linecap="round"/>
                </svg>'
            ],
            'twin-leaves' => [
                'label' => '🌱 Fresh Sprout (Twin Green Leaves)',
                'default_line1' => 'ALL NATURAL',
                'default_line2' => 'FARM FRESH',
                'svg' => '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M32 52V26" stroke="#2D231E" stroke-width="3" stroke-linecap="round"/>
                    <path d="M32 26C32 15 43 10 50 12C50 24 40 28 32 26Z" stroke="#2D231E" stroke-width="2.6" fill="#00B259"/>
                    <path d="M32 34C32 25 21 21 14 23C14 33 24 36 32 34Z" stroke="#2D231E" stroke-width="2.6" fill="none"/>
                    <path d="M14 23C22 28 28 31 32 34" stroke="#00B259" stroke-width="2.2"/>
                    <path d="M22 52H42" stroke="#2D231E" stroke-width="2.8" stroke-linecap="round"/>
                </svg>'
            ],

            // ==============================================================
            // 5. ACCURATE SMILE ICONS (HAPPY CUSTOMERS - Exact Reference)
            // ==============================================================
            'happy-customers' => [
                'label' => '👥 Happy Customers (Twin Faces with Dot Eyes & Green Smile - Reference)',
                'default_line1' => '10Lac+ HAPPY',
                'default_line2' => 'CUSTOMERS',
                'svg' => '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Big Smiley on Left -->
                    <circle cx="23" cy="27" r="15" stroke="#2D231E" stroke-width="2.8" fill="none"/>
                    <!-- Solid Dot Eyes -->
                    <circle cx="18.5" cy="24" r="1.8" fill="#2D231E"/>
                    <circle cx="27.5" cy="24" r="1.8" fill="#2D231E"/>
                    <!-- Upward Green Curved Smile -->
                    <path d="M18 29.5 C20.5 35 25.5 35 28 29.5" stroke="#00B259" stroke-width="2.8" stroke-linecap="round" fill="none"/>
                    
                    <!-- Small Smiley on Right -->
                    <circle cx="43" cy="35" r="11" stroke="#2D231E" stroke-width="2.6" fill="#FAF8F5"/>
                    <!-- Solid Dot Eyes -->
                    <circle cx="39.5" cy="32.5" r="1.5" fill="#2D231E"/>
                    <circle cx="46.5" cy="32.5" r="1.5" fill="#2D231E"/>
                    <!-- Upward Green Curved Smile -->
                    <path d="M39 37 C41 41 45 41 47 37" stroke="#00B259" stroke-width="2.4" stroke-linecap="round" fill="none"/>
                </svg>'
            ],

            // ==============================================================
            // 6. HEALTH & PRESERVATION BADGES (Exact Matching References)
            // ==============================================================
            'no-palm-oil' => [
                'label' => '🌴 No Palm Oil Used (Lobed Palm Tree + Diagonal Slash - Exact Reference)',
                'default_line1' => 'NO PALM',
                'default_line2' => 'OIL USED',
                'svg' => '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Palm Tree Trunk with bark segments matching zoom_palm.png -->
                    <path d="M33 54 C32 44 32.5 34 35 24" stroke="#2D231E" stroke-width="2.8" stroke-linecap="round"/>
                    <path d="M29 54 C28 44 28.5 34 31 24" stroke="#2D231E" stroke-width="2.8" stroke-linecap="round"/>
                    <path d="M25 54 H37" stroke="#2D231E" stroke-width="2.6" stroke-linecap="round"/>
                    <path d="M29 48 H32.5 M29.5 42 H33 M30 36 H33.5 M30.5 30 H34" stroke="#2D231E" stroke-width="1.8" stroke-linecap="round"/>
                    <!-- Scalloped Lobed Fronds matching reference photo -->
                    <path d="M33 24 C37 18 45 15 50 17 C48 19 46 20 48 22 C46 23 42 23 33 24 Z" stroke="#2D231E" stroke-width="2.2" stroke-linejoin="round" fill="#FAF8F5"/>
                    <path d="M33 24 C41 24 49 26 53 32 C49 33 46 32 46 35 C43 33 38 29 33 24 Z" stroke="#2D231E" stroke-width="2.2" stroke-linejoin="round" fill="#FAF8F5"/>
                    <path d="M33 24 C28 17 20 16 16 19 C18 21 20 22 18 24 C21 24 25 24 33 24 Z" stroke="#2D231E" stroke-width="2.2" stroke-linejoin="round" fill="#FAF8F5"/>
                    <path d="M33 24 C24 22 17 24 13 30 C17 31 20 30 20 33 C23 31 28 27 33 24 Z" stroke="#2D231E" stroke-width="2.2" stroke-linejoin="round" fill="#FAF8F5"/>
                    <!-- Diagonal Orange Slash -->
                    <line x1="12" y1="14" x2="54" y2="52" stroke="#E85D26" stroke-width="3.4" stroke-linecap="round"/>
                </svg>'
            ],
            'zero-preservatives' => [
                'label' => '🚫 Zero Preservatives (Apothecary Jar + Cross + Green Slash - Reference)',
                'default_line1' => 'ZERO',
                'default_line2' => 'PRESERVATIVES',
                'svg' => '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="23" y="14" width="18" height="5" rx="2.5" stroke="#2D231E" stroke-width="2.8" fill="none"/>
                    <path d="M26 19C26 23 20 25 20 30V45C20 49 23.5 51 28 51H36C40.5 51 44 49 44 45V30C44 25 38 23 38 19" stroke="#2D231E" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                    <path d="M32 32V38M29 35H35" stroke="#2D231E" stroke-width="2.4" stroke-linecap="round"/>
                    <line x1="16" y1="12" x2="48" y2="52" stroke="#00B259" stroke-width="3.2" stroke-linecap="round"/>
                </svg>'
            ],
            'no-preservatives' => [
                'label' => '🚫 Zero Preservatives (Alias)',
                'default_line1' => 'ZERO',
                'default_line2' => 'PRESERVATIVES',
                'svg' => '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="23" y="14" width="18" height="5" rx="2.5" stroke="#2D231E" stroke-width="2.8" fill="none"/>
                    <path d="M26 19C26 23 20 25 20 30V45C20 49 23.5 51 28 51H36C40.5 51 44 49 44 45V30C44 25 38 23 38 19" stroke="#2D231E" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                    <path d="M32 32V38M29 35H35" stroke="#2D231E" stroke-width="2.4" stroke-linecap="round"/>
                    <line x1="16" y1="12" x2="48" y2="52" stroke="#00B259" stroke-width="3.2" stroke-linecap="round"/>
                </svg>'
            ],
            'no-sugar' => [
                'label' => '🍬 No Added Sugar (Overlapping Sugar Cubes + Green Slash - Reference)',
                'default_line1' => 'NO ADDED',
                'default_line2' => 'SUGAR',
                'svg' => '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="25" y="15" width="16" height="16" rx="3" stroke="#2D231E" stroke-width="2.8" fill="none"/>
                    <rect x="17" y="24" width="16" height="16" rx="3" stroke="#2D231E" stroke-width="2.8" fill="#FAF8F5"/>
                    <line x1="13" y1="12" x2="51" y2="52" stroke="#00B259" stroke-width="3.2" stroke-linecap="round"/>
                </svg>'
            ],
            'lab-tested' => [
                'label' => '🧪 Lab Tested Nutrition (Chemical Flask + Shield Check - Reference)',
                'default_line1' => 'LAB TESTED',
                'default_line2' => 'NUTRITION',
                'svg' => '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M25 12H35M27 12V20L15 48C14 50 15.5 52 18 52H42C44.5 52 46 50 45 48L33 20V12" stroke="#2D231E" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                    <circle cx="23" cy="46" r="1.6" fill="#2D231E"/>
                    <circle cx="30" cy="48" r="1.2" fill="#2D231E"/>
                    <circle cx="26" cy="38" r="1.4" fill="#00B259"/>
                    <path d="M34 23L45 19L56 23V33C56 42 45 48 45 48C45 48 34 42 34 33Z" stroke="#2D231E" stroke-width="2.6" stroke-linejoin="round" fill="#FAF8F5"/>
                    <path d="M40 32.5L44 36.5L51 28" stroke="#00B259" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>'
            ],
            'gluten-free' => [
                'label' => '🌾 Gluten Free (Wheat Ear Slash)',
                'default_line1' => '100% GLUTEN',
                'default_line2' => 'FREE',
                'svg' => '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M32 54V12" stroke="#2D231E" stroke-width="2.8" stroke-linecap="round"/>
                    <path d="M32 17C36 13 41 14 42 18C38 22 34 21 32 17Z" stroke="#2D231E" stroke-width="2.2" fill="none"/>
                    <path d="M32 17C28 13 23 14 22 18C26 22 30 21 32 17Z" stroke="#2D231E" stroke-width="2.2" fill="none"/>
                    <path d="M32 27C36 23 41 24 42 28C38 32 34 31 32 27Z" stroke="#2D231E" stroke-width="2.2" fill="none"/>
                    <path d="M32 27C28 23 23 24 22 28C26 32 30 31 32 27Z" stroke="#2D231E" stroke-width="2.2" fill="none"/>
                    <path d="M32 37C36 33 41 34 42 38C38 42 34 41 32 37Z" stroke="#2D231E" stroke-width="2.2" fill="none"/>
                    <path d="M32 37C28 33 23 34 22 38C26 42 30 41 32 37Z" stroke="#2D231E" stroke-width="2.2" fill="none"/>
                    <line x1="10" y1="52" x2="54" y2="12" stroke="#E85D26" stroke-width="3.6" stroke-linecap="round"/>
                </svg>'
            ],
            'vegan-friendly' => [
                'label' => '🌱 100% Vegan (Plant V Emblem)',
                'default_line1' => '100% PLANT',
                'default_line2' => 'BASED VEGAN',
                'svg' => '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="32" cy="32" r="23" stroke="#2D231E" stroke-width="2.8" fill="none"/>
                    <path d="M22 22L32 44L42 22" stroke="#2D231E" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M32 44C34 33 43 24 50 20C47 31 39 40 32 44Z" fill="#00B259" stroke="#2D231E" stroke-width="2"/>
                </svg>'
            ],
            'zero-cholesterol' => [
                'label' => '❤️ Zero Cholesterol (Heart Cardio Pulse)',
                'default_line1' => 'ZERO',
                'default_line2' => 'CHOLESTEROL',
                'svg' => '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M32 52L14 34C9 29 9 19 16 14C23 9 30 13 32 17C34 13 41 9 48 14C55 19 55 29 50 34L32 52Z" stroke="#2D231E" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                    <path d="M18 32H25L28 26L33 39L37 28L40 32H46" stroke="#00B259" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>'
            ],
            'high-protein' => [
                'label' => '⚡ High Protein & Energy (Power Bolt)',
                'default_line1' => 'HIGH PROTEIN',
                'default_line2' => '& ENERGY',
                'svg' => '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="32" cy="32" r="23" stroke="#2D231E" stroke-width="2.8" fill="none"/>
                    <path d="M34 13L21 34H33L30 51L43 30H31L34 13Z" stroke="#2D231E" stroke-width="2.6" stroke-linejoin="round" fill="#00B259"/>
                </svg>'
            ],
            'rich-antioxidants' => [
                'label' => '✨ Rich in Antioxidants (Superfood Sparkles)',
                'default_line1' => 'RICH IN',
                'default_line2' => 'ANTIOXIDANTS',
                'svg' => '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M32 10L35 25L50 28L35 31L32 46L29 31L14 28L29 25L32 10Z" stroke="#2D231E" stroke-width="2.6" stroke-linejoin="round" fill="#FAF8F5"/>
                    <path d="M47 38L48.5 45L55.5 46.5L48.5 48L47 55L45.5 48L38.5 46.5L45.5 45L47 38Z" fill="#00B259"/>
                    <circle cx="18" cy="46" r="3" stroke="#00B259" stroke-width="2.2" fill="none"/>
                </svg>'
            ],
            'premium-grade' => [
                'label' => '💎 Premium Grade (Royal Cut Diamond)',
                'default_line1' => 'PREMIUM',
                'default_line2' => 'GRADE NUTS',
                'svg' => '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 19L32 8L46 19L32 54L18 19Z" stroke="#2D231E" stroke-width="2.8" stroke-linejoin="round" fill="none"/>
                    <path d="M9 19H55L32 54L9 19Z" stroke="#2D231E" stroke-width="2.8" stroke-linejoin="round"/>
                    <path d="M18 19L32 54L46 19" stroke="#00B259" stroke-width="2.4"/>
                    <path d="M9 19L32 8L55 19" stroke="#2D231E" stroke-width="2.8"/>
                </svg>'
            ],
            'hand-picked' => [
                'label' => '🤲 Hand-Picked Quality (Caring Hands)',
                'default_line1' => 'HAND',
                'default_line2' => 'PICKED',
                'svg' => '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M11 41C15 39 22 38 28 40L39 45C43 47 47 45 48 41L49 37C50 34 47 31 44 32L32 31C28 31 25 30 22 27L19 23C17 21 14 22 13.5 25L11 41Z" stroke="#2D231E" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                    <path d="M35 14C33 11 29 11 28 14C27 11 23 11 21 14C19 18 25 23 28 26C31 23 37 18 35 14Z" fill="#00B259" stroke="#2D231E" stroke-width="2"/>
                </svg>'
            ],
            'sun-dried' => [
                'label' => '☀️ Naturally Sun-Dried (Golden Sun Rays)',
                'default_line1' => 'NATURALLY',
                'default_line2' => 'SUN DRIED',
                'svg' => '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="32" cy="32" r="13" stroke="#2D231E" stroke-width="2.8" fill="none"/>
                    <path d="M32 7V12M32 52V57M7 32H12M52 32H57M14 14L18 18M46 46L50 50M14 50L18 46M46 18L50 14" stroke="#E85D26" stroke-width="2.8" stroke-linecap="round"/>
                </svg>'
            ],
            'vacuum-packed' => [
                'label' => '📦 Vacuum Sealed Freshness (Airtight Lock)',
                'default_line1' => 'VACUUM SEALED',
                'default_line2' => 'MAX FRESHNESS',
                'svg' => '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="16" y="14" width="32" height="42" rx="4" stroke="#2D231E" stroke-width="2.8" fill="none"/>
                    <path d="M16 22H48" stroke="#2D231E" stroke-width="2"/>
                    <path d="M26 14V10C26 8.5 27.5 7 29 7H35C36.5 7 38 8.5 38 10V14" stroke="#2D231E" stroke-width="2.4"/>
                    <circle cx="32" cy="35" r="7" stroke="#00B259" stroke-width="2.6" fill="none"/>
                    <path d="M32 31V35L35 37" stroke="#00B259" stroke-width="2.2" stroke-linecap="round"/>
                </svg>'
            ],
            'fast-shipping' => [
                'label' => '🚚 Express Delivery (Transport Van)',
                'default_line1' => 'EXPRESS',
                'default_line2' => 'DELIVERY',
                'svg' => '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M8 20H37V42H8V20Z" stroke="#2D231E" stroke-width="2.8" stroke-linejoin="round" fill="none"/>
                    <path d="M37 27H48L55 34V42H37V27Z" stroke="#2D231E" stroke-width="2.8" stroke-linejoin="round" fill="none"/>
                    <circle cx="19" cy="44" r="5.5" stroke="#2D231E" stroke-width="2.6" fill="#FAF8F5"/>
                    <circle cx="47" cy="44" r="5.5" stroke="#2D231E" stroke-width="2.6" fill="#FAF8F5"/>
                    <line x1="3" y1="27" x2="5" y2="27" stroke="#00B259" stroke-width="2.6" stroke-linecap="round"/>
                    <line x1="2" y1="33" x2="5" y2="33" stroke="#00B259" stroke-width="2.6" stroke-linecap="round"/>
                </svg>'
            ],
            'secure-checkout' => [
                'label' => '🔒 100% Secure Checkout (Shield & Lock)',
                'default_line1' => '100% SECURE',
                'default_line2' => 'CHECKOUT',
                'svg' => '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M32 7L51 15V29C51 42 43 51 32 56C21 51 13 42 13 29V15L32 7Z" stroke="#2D231E" stroke-width="2.8" stroke-linejoin="round" fill="none"/>
                    <rect x="25" y="30" width="14" height="12" rx="2.5" stroke="#2D231E" stroke-width="2.4" fill="none"/>
                    <path d="M28 30V24C28 21.8 29.8 20 32 20C34.2 20 36 21.8 36 24V30" stroke="#2D231E" stroke-width="2.4" stroke-linecap="round"/>
                    <circle cx="32" cy="35.5" r="1.8" fill="#00B259"/>
                </svg>'
            ]
        ];

        // ==============================================================
        // AUTOMATIC CUSTOM UPLOAD SCANNER (Permanently Saves & Reflects in Library)
        // ==============================================================
        $badges_dir = dirname(__DIR__) . '/assets/images/badges';
        if (!is_dir($badges_dir)) {
            $badges_dir = __DIR__ . '/../assets/images/badges';
        }
        if (!is_dir($badges_dir)) {
            $badges_dir = realpath('assets/images/badges');
        }
        if (!is_dir($badges_dir)) {
            $badges_dir = realpath('../assets/images/badges');
        }

        $custom_icons = [];
        if ($badges_dir && is_dir($badges_dir)) {
            $manifest_file = $badges_dir . DIRECTORY_SEPARATOR . 'manifest.json';
            $manifest = file_exists($manifest_file) ? json_decode(file_get_contents($manifest_file), true) : [];
            if (!is_array($manifest)) $manifest = [];

            $files = scandir($badges_dir);
            rsort($files); // Newest custom uploads first
            foreach ($files as $file) {
                if ($file === '.' || $file === '..' || $file === 'manifest.json') continue;
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($ext, ['svg', 'png', 'webp', 'jpg', 'jpeg'])) {
                    $rel_path = 'assets/images/badges/' . $file;
                    $key = $rel_path;
                    
                    // Fetch original or human-friendly label
                    $label_name = $manifest[$file]['name'] ?? pathinfo($file, PATHINFO_FILENAME);
                    $clean_label = ucwords(str_replace(['custom_badge_', 'custom_', '_', '-'], ['', '', ' ', ' '], $label_name));
                    if (empty(trim($clean_label))) $clean_label = $file;

                    // Inline native SVG or provide responsive image tag
                    $full_file_path = $badges_dir . DIRECTORY_SEPARATOR . $file;
                    $svg_content = '';
                    if ($ext === 'svg' && file_exists($full_file_path)) {
                        $raw_svg = file_get_contents($full_file_path);
                        if (preg_match('/<svg[\s\S]*<\/svg>/i', $raw_svg, $matches)) {
                            $svg_content = $matches[0];
                        }
                    }
                    if (empty($svg_content)) {
                        $prefix = (basename(dirname($_SERVER['SCRIPT_FILENAME'] ?? '')) === 'admin' || str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/')) ? '../' : '';
                        $svg_content = '<img src="' . $prefix . htmlspecialchars($rel_path) . '" alt="' . htmlspecialchars($clean_label) . '" class="w-full h-full object-contain" />';
                    }

                    $custom_icons[$key] = [
                        'label' => '📁 ' . trim($clean_label) . ' (Custom)',
                        'default_line1' => strtoupper(substr(trim($clean_label), 0, 15)),
                        'default_line2' => '',
                        'is_custom' => true,
                        'file_path' => $rel_path,
                        'svg' => $svg_content
                    ];
                }
            }
        }

        // Filter out any hidden icons
        $hidden_json = function_exists('get_setting') ? get_setting('hidden_badge_icons', '[]') : '[]';
        $hidden_keys = json_decode($hidden_json, true) ?: [];
        if (!empty($hidden_keys) && is_array($hidden_keys)) {
            foreach ($hidden_keys as $hk) {
                unset($library[$hk]);
                unset($custom_icons[$hk]);
            }
        }

        if (!empty($custom_icons)) {
            return $custom_icons + $library;
        }
        return $library;
    }
}

if (!function_exists('render_trust_badge_icon')) {
    function render_trust_badge_icon($iconKey, $extraClass = 'w-14 h-14') {
        $iconKey = trim($iconKey ?? '');
        $lib = get_trust_badge_icon_library();

        // 1. Direct SVG File Inlining if an SVG path is given
        if (!empty($iconKey)) {
            $check_file = file_exists($iconKey) ? $iconKey : (file_exists('../' . $iconKey) ? '../' . $iconKey : '');
            if ($check_file && str_ends_with(strtolower($check_file), '.svg')) {
                $raw = file_get_contents($check_file);
                if (preg_match('/<svg[\s\S]*<\/svg>/i', $raw, $m)) {
                    $svg = $m[0];
                    if (!str_contains($svg, 'viewBox') && preg_match('/width="([0-9.]+)"\s+height="([0-9.]+)"/i', $svg, $wh)) {
                        $svg = str_replace('<svg ', '<svg viewBox="0 0 ' . $wh[1] . ' ' . $wh[2] . '" ', $svg);
                    }
                    return str_replace('<svg ', '<svg class="' . htmlspecialchars($extraClass) . '" ', $svg);
                }
            }

            // Direct raster image path
            if (str_starts_with($iconKey, 'assets/') || str_starts_with($iconKey, 'uploads/') || preg_match('/\.(png|webp|jpg|jpeg)$/i', $iconKey)) {
                $prefix = file_exists($iconKey) ? '' : (file_exists('../' . $iconKey) ? '../' : '');
                $src = (str_starts_with($iconKey, 'http://') || str_starts_with($iconKey, 'https://')) ? $iconKey : $prefix . ltrim($iconKey, '/');
                return '<img src="' . htmlspecialchars($src) . '" alt="badge icon" class="' . htmlspecialchars($extraClass) . ' object-contain" loading="lazy" />';
            }
            
            // Raw SVG string provided
            if (str_starts_with($iconKey, '<svg') || str_contains($iconKey, '</svg>')) {
                return str_replace('<svg ', '<svg class="' . htmlspecialchars($extraClass) . '" ', $iconKey);
            }
        }

        // 2. Normalize alias keys
        if ($iconKey === 'no-preservatives' && isset($lib['zero-preservatives'])) {
            $iconKey = 'zero-preservatives';
        }
        if ($iconKey === 'secure' && isset($lib['secure-checkout'])) {
            $iconKey = 'secure-checkout';
        }
        if ($iconKey === 'num-6' && isset($lib['quality-check'])) {
            $iconKey = 'quality-check';
        }
        if ($iconKey === 'leaf' && isset($lib['organic'])) {
            $iconKey = 'organic';
        }
        
        // 3. Match curated or custom library
        if (!empty($iconKey) && isset($lib[$iconKey])) {
            $item = $lib[$iconKey];
            $svg = $item['svg'] ?? '';
            if (str_starts_with($svg, '<svg') || str_contains($svg, '</svg>')) {
                return str_replace('<svg ', '<svg class="' . htmlspecialchars($extraClass) . '" ', $svg);
            } elseif (str_starts_with($svg, '<img')) {
                $prefix = file_exists($iconKey) ? '' : (file_exists('../' . $iconKey) ? '../' : '');
                $src = $prefix . ltrim($iconKey, '/');
                return '<img src="' . htmlspecialchars($src) . '" alt="' . htmlspecialchars($item['label'] ?? 'badge') . '" class="' . htmlspecialchars($extraClass) . ' object-contain" loading="lazy" />';
            }
            return $svg;
        }
        
        // 4. Match FontAwesome class (e.g. "fas fa-leaf")
        if (!empty($iconKey) && (str_contains($iconKey, 'fa-') || str_contains($iconKey, 'fas ') || str_contains($iconKey, 'far '))) {
            return '<i class="' . htmlspecialchars($iconKey) . ' ' . htmlspecialchars($extraClass) . ' flex items-center justify-center"></i>';
        }

        // 5. Fallback default
        if (isset($lib['zero-preservatives'])) {
            return str_replace('<svg ', '<svg class="' . htmlspecialchars($extraClass) . '" ', $lib['zero-preservatives']['svg']);
        }
        
        return '<i class="fas fa-shield-alt ' . htmlspecialchars($extraClass) . '"></i>';
    }
}

if (!function_exists('get_default_trust_badges_data')) {
    function get_default_trust_badges_data() {
        return [
            ['icon' => 'kashmir',            'title' => 'DIRECT FROM',       'subtitle' => 'KASHMIR',             'bg_color' => '', 'icon_color' => '', 'sort_order' => 1],
            ['icon' => 'zero-preservatives', 'title' => 'ZERO',              'subtitle' => 'PRESERVATIVES',       'bg_color' => '', 'icon_color' => '', 'sort_order' => 2],
            ['icon' => 'no-sugar',           'title' => 'NO ADDED',          'subtitle' => 'SUGAR',               'bg_color' => '', 'icon_color' => '', 'sort_order' => 3],
            ['icon' => 'made-in-india',      'title' => 'MADE IN INDIA',     'subtitle' => 'FOR INDIA',           'bg_color' => '', 'icon_color' => '', 'sort_order' => 4],
            ['icon' => 'happy-customers',    'title' => '10Lac+ HAPPY',      'subtitle' => 'CUSTOMERS',           'bg_color' => '', 'icon_color' => '', 'sort_order' => 5],
            ['icon' => 'quality-check',      'title' => '6 LAYER',           'subtitle' => 'QUALITY CHECK',       'bg_color' => '', 'icon_color' => '', 'sort_order' => 6],
            ['icon' => 'lab-tested',         'title' => 'LAB TESTED',        'subtitle' => 'NUTRITION',           'bg_color' => '', 'icon_color' => '', 'sort_order' => 7],
            ['icon' => 'no-palm-oil',        'title' => 'NO PALM',           'subtitle' => 'OIL USED',            'bg_color' => '', 'icon_color' => '', 'sort_order' => 8],
            ['icon' => 'fssai',              'title' => 'FSSAI',             'subtitle' => 'CERTIFIED',           'bg_color' => '', 'icon_color' => '', 'sort_order' => 9],
            ['icon' => 'organic',            'title' => '100% ORGANIC',      'subtitle' => '& NATURAL',           'bg_color' => '', 'icon_color' => '', 'sort_order' => 10],
            ['icon' => 'premium-grade',      'title' => 'PREMIUM',           'subtitle' => 'GRADE NUTS',          'bg_color' => '', 'icon_color' => '', 'sort_order' => 11],
            ['icon' => 'sun-dried',          'title' => 'NATURALLY',         'subtitle' => 'SUN DRIED',           'bg_color' => '', 'icon_color' => '', 'sort_order' => 12],
        ];
    }
}

if (!function_exists('seed_default_trust_badges')) {
    function seed_default_trust_badges() {
        global $conn;
        if (!$conn) $conn = get_db_connection();
        
        $conn->query("DELETE FROM trust_badges");
        
        $defaults = get_default_trust_badges_data();
        $stmt = $conn->prepare("INSERT INTO trust_badges (icon, title, subtitle, bg_color, icon_color, is_active, sort_order) VALUES (?, ?, ?, ?, ?, 1, ?)");
        foreach ($defaults as $d) {
            $stmt->bind_param("sssssi", $d['icon'], $d['title'], $d['subtitle'], $d['bg_color'], $d['icon_color'], $d['sort_order']);
            $stmt->execute();
        }
        return true;
    }
}
