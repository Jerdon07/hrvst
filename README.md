# Coordination and Forecast System

### Solving Wasted Vegetable Supplies Caused by Market Blindness in partnership with La Trinidad Vegetable Trading Post

<img src="public/images/readme/welcome.png" alt="App Preview" width="500" align="center">

---

## 🚩 Problem: Post-Harvest Waste

Currently, the La Trinidad Vegetable Trading Post operates on Information Asymmetry. Farmers and dealers operate in "blind" silos, leading to Wasted Vegetable Supplies and Unmet Market Demands.

1. **The Oversupply Cycle:** Farmers often harvest based on current high prices without knowing how many other farmers are doing the same. This leads to "glut" days where supply far outstripe demand, forcing farmers to sell at a loss or waste their crops.
2. **Under-Demand & Scarcity:** Dealers may arrive at the trading post only to find a shortage of the specific crops they need because there was no way to communicate their demand to the farming community in advance.
3. **Logistical Chaos:** Without a coordinated schedule, the trading post experience unpredictable "peak" hours that strain resources and cause unneccessary delays for all stakeholders.

---

### 🎯 The Goal: Market Synchronization

Hrvst aims to transform the Trading Post from a chaotic marketplace into a **Just-in-time (JIT) Supply Chain**. The goal of this project is to provide the digital infrastructure necessary to:

- **Align Supply with Real-Time Demand:** Allow farmers to time their deliveries to match the specific windows when dealers are scheduled to arrive.
- **Enable Production Intelligence:** Move farmers away from reactive planting and toward proactive, data-driven crop selection using historical trends.
- **Reduce Post-Harvest Waste:** Ensure that every kilo of produce brought to the Trading Post has a higher probability of being sold at a fair market price.

---

## ✨ Key Features & Economic Impact

| Feature                             | Technical Function                                  | Economic Significance                                                                                                 |
| ----------------------------------- | --------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------- |
| **Demand-Driven Scheduling**        | Farmers and Dealers schedule arrivals.              | **Load Balancing:** Prevents "glut" days by spreading deliveries across whe week.                                     |
| **Market Intelligence (Analytics)** | Supply and demand history visualization.            | **Strategic Planting:** Stops the cycle of surpluses by showing farmers what to plant before the market is saturated. |
| **Transparent Marketplace**         | Mutual posting of available stock and dealer needs. | **Reduced Search Costs:** Connects farmers directly to the demand, ensuring a "guaranteed exit" for their crops.      |

<table align="center">
  <tr>
    <td align="center" width="35%">
      <img src="public/images/readme/img-1.png" alt="App Preview 1">
    </td>
    <td align="center" width="40%">
      <img src="public/images/readme/img-2.png" alt="App Preview 2">
    </td>
    <td align="center" width="25%">
      <img src="public/images/readme/img-3.png" alt="App Preview 3">
    </td>
  </tr>
</table>

---

### 🛠️ Tech Stack

![Static Badge](https://img.shields.io/badge/Laravel-black?style=for-the-badge&logo=laravel&link=https%3A%2F%2Flaravel.com%2Fdocs%2F13.x)
![Static Badge](https://img.shields.io/badge/Vue-black?style=for-the-badge&logo=vuedotjs&link=https%3A%2F%2Fvuejs.org%2Fguide%2Fintroduction.html)
![Static Badge](https://img.shields.io/badge/Supabase-black?style=for-the-badge&logo=supabase&link=https%3A%2F%2Fsupabase.com%2Fdocs)
![Static Badge](https://img.shields.io/badge/Inertia-black?style=for-the-badge&logo=inertia&link=https%3A%2F%2Finertiajs.com%2F)

![Static Badge](https://img.shields.io/badge/Fortify-black?style=flat-square&logo=laravel)
![Static Badge](https://img.shields.io/badge/Nightwatch-black?style=flat-square&logo=laravel)
![Static Badge](https://img.shields.io/badge/Spatie-black?style=flat-square&logo=laravel)
![Static Badge](https://img.shields.io/badge/Boost-black?style=flat-square&logo=laravel)
![Static Badge](https://img.shields.io/badge/Pint-black?style=flat-square&logo=laravel)
![Static Badge](https://img.shields.io/badge/Pest-black?style=flat-square&logo=laravel)

![Static Badge](https://img.shields.io/badge/TypeScript-black?style=flat-square&logo=typescript)
![Static Badge](https://img.shields.io/badge/Tailwind-black?style=flat-square&logo=tailwindcss)
![Static Badge](https://img.shields.io/badge/Shadcn%20UI-black?style=flat-square&logo=shadcnui)
![Static Badge](https://img.shields.io/badge/Leaflet-black?style=flat-square&logo=leaflet&logoColor=%23199900)
![Static Badge](https://img.shields.io/badge/TanStack-black?style=flat-square&logo=tanstack)
![Static Badge](https://img.shields.io/badge/Chart.js-black?style=flat-square&logo=chartdotjs)
![Static Badge](https://img.shields.io/badge/Prettier-black?style=flat-square&logo=prettier)
![Static Badge](https://img.shields.io/badge/ESLint-black?style=flat-square&logo=eslint&logoColor=%234B32C3)
![Static Badge](https://img.shields.io/badge/Wayfinder-black?style=flat-square&logo=laravel)

---

### 🚀 Getting Started

1.  **Clone the Repo:** `git clone https://github.com/Cresco-Team/Hrvst-v2.git` then `cd Hrvst-v2`
2.  **Install Run Setup:** `composer setup`
4.  **Database & Storage:** `php artisan db:seed` then `php storage:link`
5.  **Run Development Server:** `php artisan dev`

### Run these checklist commands before PR

1. Install Dependencies:
    - `composer install --no-interaction --prefer-dist`
    - Stop active servers then run `npm ci`
2. PHP Lint
    - `vendor/bin/pint --test`
    - If it fails, run `vendor/bin/pint`
3. PHP Dependency Audit
    - `composer audit` hecks installed packages against known security advisories
4. TS Lint
    - `npx eslint .`
    - If it fails, run `npx eslint . --fix`
5. TS Formatting
    - `npm run format:check`
    - If it fails, run `npm run format`
6. TS Type Check
    - `npx vue-tsc --noEmit`
    - Type errors require manual correction
7. Tests
    - `vendor/bin/pest --compact`
    - `npm run test:unit`
8. Wayfinder Routes
    - Run `php artisan wayfinder:generate --no-interaction` if you touched routes/controllers

#### One-Line Version

- `composer install && npm ci \
&& vendor/bin/pint --test \
&& composer audit \
&& npx eslint . \
&& npm run format:check \
&& npx vue-tsc --noEmit \
&& vendor/bin/pest --compact`

---

**Developed by Team Cresco**
_Lead Developer:_ Jerdon M. Litaoen
