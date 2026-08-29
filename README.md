# Pipes

A Yahoo Pipes-style WordPress app powered by [WpApp](https://github.com/akirk/wp-app).

[Try Pipes in WordPress Playground](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/akirk/pipes/main/blueprint.json) · [Try it with demo data](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/akirk/pipes/main/demo.json)

[Try it in OpenStation](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/akirk/pipes/main/blueprint-openstation.json) — the same app opened in desktop mode with the [OpenStation](https://github.com/WordPress/openstation) plugin.

Pipes discovers abilities registered through the WordPress Abilities API, lets a logged-in user compose them into saved flows, and runs those flows through REST endpoints. Each node can define base JSON arguments and bind named input fields to values from earlier node results using dot paths.
