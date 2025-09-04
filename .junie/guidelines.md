Laravel 11 kernel-less structure, strict types where reasonable

* Vue 3 Options API only (no Composition API, no TypeScript)
* Single Build: Vite bundles the SPA served from /public (e.g., compiled assets + fallback
  route)
* Vue Router + built-in reactivity
* No CSS frameworks (Tailwind, Bootstrap, etc.) Plain CSS using BEM naming (.ticket-list__item--active, etc.).
* Keep third-party packages minimal: DB driver, queue driver, openai-php/laravel, Chart.js (or
  vanilla)

# Application

Smart Ticket Triage & Dashboard — Take-Home Task
Goal
Build a production-style single-page application that lets a help-desk team:
1st Submit support tickets
2nd Queue an AI classification job
3rd View / filter tickets & see a small analytics dashboard
4th Override the AI’s category and add or edit an internal note
