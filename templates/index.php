<!DOCTYPE html>
<html <?php wp_app_language_attributes(); ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php wp_app_title(); ?></title>
    <?php wp_app_head(); ?>
    <style>
        :root {
            color-scheme: light dark;
            --pipes-bg: var(--wp-app-color-background, #f6f7f8);
            --pipes-surface: var(--wp-app-color-surface, #fff);
            --pipes-surface-alt: var(--wp-app-color-surface-alt, #eef1f4);
            --pipes-text: var(--wp-app-color-text, #1f2328);
            --pipes-muted: var(--wp-app-color-muted, #667085);
            --pipes-border: color-mix(in srgb, var(--pipes-muted) 24%, transparent);
            --pipes-link: var(--wp-app-color-link, #2368cc);
            --pipes-accent: #147d64;
            --pipes-danger: #b42318;
            --pipes-radius: 6px;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background: var(--pipes-bg);
            color: var(--pipes-text);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }
        button, input, textarea, select {
            font: inherit;
            color: inherit;
        }
        button {
            border: 1px solid var(--pipes-border);
            background: var(--pipes-surface);
            border-radius: var(--pipes-radius);
            padding: 0.55rem 0.75rem;
            cursor: pointer;
        }
        button:hover { border-color: var(--pipes-link); }
        button.primary {
            background: var(--pipes-accent);
            border-color: var(--pipes-accent);
            color: #fff;
        }
        button.danger {
            border-color: color-mix(in srgb, var(--pipes-danger) 42%, var(--pipes-border));
            color: var(--pipes-danger);
        }
        input, textarea, select {
            width: 100%;
            border: 1px solid var(--pipes-border);
            border-radius: var(--pipes-radius);
            background: var(--pipes-surface);
            padding: 0.55rem 0.65rem;
        }
        input[type="checkbox"] {
            width: auto;
            padding: 0;
        }
        textarea {
            min-height: 8rem;
            resize: vertical;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace;
            font-size: 0.86rem;
            line-height: 1.45;
        }
        .app {
            display: grid;
            grid-template-columns: 18rem minmax(24rem, 1fr) 24rem;
            min-height: 100vh;
        }
        .sidebar, .inspector {
            border-color: var(--pipes-border);
            background: var(--pipes-surface);
            min-width: 0;
        }
        .sidebar {
            border-right: 1px solid var(--pipes-border);
            padding: 1rem;
            overflow: auto;
        }
        .inspector {
            border-left: 1px solid var(--pipes-border);
            padding: 1rem;
            overflow: auto;
        }
        .workspace {
            min-width: 0;
            display: grid;
            grid-template-rows: auto 1fr auto;
        }
        .topbar {
            display: grid;
            grid-template-columns: minmax(12rem, 1fr) auto;
            gap: 0.75rem;
            align-items: center;
            border-bottom: 1px solid var(--pipes-border);
            background: var(--pipes-surface);
            padding: 0.9rem 1rem;
        }
        .title-row {
            display: grid;
            grid-template-columns: minmax(8rem, 24rem) auto;
            align-items: center;
            gap: 0.75rem;
        }
        .actions {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
            flex-wrap: wrap;
        }
        .mode-toggle {
            display: inline-flex;
            border: 1px solid var(--pipes-border);
            border-radius: var(--pipes-radius);
            overflow: hidden;
        }
        .mode-toggle button {
            border: 0;
            border-radius: 0;
        }
        .mode-toggle button.active {
            background: var(--pipes-accent);
            color: #fff;
        }
        .brand {
            font-size: 1.35rem;
            font-weight: 700;
            margin: 0 0 0.9rem;
        }
        .section-title {
            margin: 1.2rem 0 0.55rem;
            font-size: 0.78rem;
            color: var(--pipes-muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .search {
            margin-bottom: 0.75rem;
        }
        .list {
            display: grid;
            gap: 0.45rem;
        }
        .list-item {
            display: grid;
            gap: 0.25rem;
            width: 100%;
            text-align: left;
            padding: 0.65rem;
        }
        .list-item.active {
            border-color: var(--pipes-accent);
            box-shadow: inset 3px 0 0 var(--pipes-accent);
        }
        .list-item strong {
            font-size: 0.9rem;
        }
        .meta {
            color: var(--pipes-muted);
            font-size: 0.78rem;
            overflow-wrap: anywhere;
        }
        .badge-row {
            display: flex;
            gap: 0.35rem;
            flex-wrap: wrap;
        }
        .badge {
            border: 1px solid var(--pipes-border);
            border-radius: 999px;
            color: var(--pipes-muted);
            font-size: 0.72rem;
            line-height: 1;
            padding: 0.28rem 0.45rem;
        }
        .badge.warn {
            border-color: color-mix(in srgb, var(--pipes-danger) 44%, var(--pipes-border));
            color: var(--pipes-danger);
        }
        .canvas {
            position: relative;
            overflow: auto;
            overscroll-behavior: contain;
            min-height: 28rem;
            padding: 1.25rem;
            background-image:
                linear-gradient(var(--pipes-border) 1px, transparent 1px),
                linear-gradient(90deg, var(--pipes-border) 1px, transparent 1px);
            background-size: 36px 36px;
            background-color: color-mix(in srgb, var(--pipes-bg) 88%, var(--pipes-surface));
        }
        .list-builder {
            display: none;
            overflow: auto;
            padding: 1rem;
            background: color-mix(in srgb, var(--pipes-bg) 88%, var(--pipes-surface));
        }
        .builder-list .canvas {
            display: none;
        }
        .builder-list .list-builder {
            display: block;
        }
        .flow-list {
            display: grid;
            gap: 0.75rem;
            max-width: 900px;
        }
        .flow-step {
            border: 1px solid var(--pipes-border);
            border-radius: var(--pipes-radius);
            background: var(--pipes-surface);
            padding: 0.85rem;
        }
        .flow-step.active {
            border-color: var(--pipes-accent);
            box-shadow: inset 4px 0 0 var(--pipes-accent);
        }
        .flow-step-header {
            align-items: start;
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            gap: 0.65rem;
        }
        .flow-step-main {
            min-width: 0;
        }
        .flow-step-number {
            align-items: center;
            background: var(--pipes-surface-alt);
            border-radius: 999px;
            display: inline-flex;
            font-weight: 700;
            height: 1.8rem;
            justify-content: center;
            width: 1.8rem;
        }
        .flow-step h2 {
            font-size: 0.95rem;
            margin: 0 0 0.2rem;
        }
        .flow-step-title {
            border-color: transparent;
            font-size: 0.95rem;
            font-weight: 700;
            margin: -0.3rem 0 0.1rem -0.45rem;
            padding: 0.3rem 0.45rem;
        }
        .flow-step-title:focus {
            border-color: var(--pipes-accent);
        }
        .flow-step-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
            justify-content: flex-start;
            margin-top: 0.75rem;
        }
        .flow-step-actions button {
            flex: 0 0 auto;
            padding: 0.35rem 0.5rem;
        }
        .list-args {
            border-top: 1px solid var(--pipes-border);
            display: grid;
            gap: 0.55rem;
            margin-top: 0.75rem;
            padding-top: 0.75rem;
        }
        .list-arg {
            display: grid;
            gap: 0.35rem;
        }
        .list-arg label {
            color: var(--pipes-muted);
            font-size: 0.78rem;
            font-weight: 600;
        }
        .list-arg textarea {
            min-height: 4rem;
        }
        .flow-add {
            border: 1px dashed var(--pipes-border);
            border-radius: var(--pipes-radius);
            background: color-mix(in srgb, var(--pipes-surface) 72%, transparent);
            padding: 0.85rem;
        }
        .flow-add-button {
            align-items: center;
            display: flex;
            gap: 0.55rem;
            justify-content: center;
            width: 100%;
        }
        .flow-add-button strong {
            align-items: center;
            background: var(--pipes-accent);
            border-radius: 999px;
            color: #fff;
            display: inline-flex;
            font-size: 1.05rem;
            height: 1.65rem;
            justify-content: center;
            width: 1.65rem;
        }
        .flow-add-picker {
            display: grid;
            gap: 0.55rem;
        }
        .flow-add-results {
            display: grid;
            gap: 0.35rem;
            max-height: 18rem;
            overflow: auto;
        }
        .graph {
            position: relative;
            min-width: 46rem;
            min-height: 34rem;
        }
        .edges {
            position: absolute;
            inset: 0;
            pointer-events: none;
            overflow: visible;
            z-index: 4;
        }
        .node {
            position: absolute;
            width: 18rem;
            min-height: 8rem;
            border: 1px solid var(--pipes-border);
            border-radius: var(--pipes-radius);
            background: var(--pipes-surface);
            box-shadow: 0 10px 25px color-mix(in srgb, #000 9%, transparent);
            padding: 0.85rem;
            cursor: grab;
            touch-action: none;
            user-select: none;
            z-index: 2;
        }
        .node.dragging {
            cursor: grabbing;
            z-index: 6;
        }
        .node.selected {
            border-color: var(--pipes-accent);
            box-shadow: 0 0 0 2px color-mix(in srgb, var(--pipes-accent) 22%, transparent), 0 10px 25px color-mix(in srgb, #000 9%, transparent);
            z-index: 3;
        }
        .node h2 {
            font-size: 0.95rem;
            margin: 0 0 0.35rem;
        }
        .node .node-id {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace;
            color: var(--pipes-muted);
            font-size: 0.76rem;
        }
        .port-groups {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.55rem;
            margin-top: 0.65rem;
        }
        .port-group-title {
            color: var(--pipes-muted);
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
            text-transform: uppercase;
        }
        .port {
            position: relative;
            display: block;
            width: 100%;
            border: 1px solid var(--pipes-border);
            background: color-mix(in srgb, var(--pipes-surface-alt) 62%, transparent);
            color: var(--pipes-text);
            font-size: 0.72rem;
            line-height: 1.2;
            margin: 0.25rem 0;
            overflow-wrap: anywhere;
            padding: 0.32rem 0.42rem;
            text-align: left;
        }
        .port::before,
        .port::after {
            content: "";
            position: absolute;
            top: 50%;
            z-index: 5;
            width: 0.55rem;
            height: 0.55rem;
            border: 2px solid var(--pipes-accent);
            border-radius: 999px;
            background: var(--pipes-surface);
            transform: translateY(-50%);
        }
        .input-port {
            padding-left: 0.7rem;
        }
        .input-port::before {
            left: -1.16rem;
        }
        .input-port::after {
            display: none;
        }
        .output-port {
            padding-right: 0.7rem;
        }
        .output-port::before {
            display: none;
        }
        .output-port::after {
            right: -1.16rem;
        }
        .port.bound {
            border-color: var(--pipes-accent);
            background: color-mix(in srgb, var(--pipes-accent) 10%, var(--pipes-surface));
        }
        .output-port.bound {
            border-color: var(--pipes-link);
            background: color-mix(in srgb, var(--pipes-link) 10%, var(--pipes-surface));
        }
        .port.active {
            box-shadow: 0 0 0 2px color-mix(in srgb, var(--pipes-accent) 20%, transparent);
        }
        .node-footer {
            display: flex;
            gap: 0.4rem;
            margin-top: 0.75rem;
        }
        .node-footer button {
            flex: 1;
            padding: 0.4rem 0.5rem;
            font-size: 0.78rem;
        }
        .empty {
            border: 1px dashed var(--pipes-border);
            border-radius: var(--pipes-radius);
            background: color-mix(in srgb, var(--pipes-surface) 72%, transparent);
            color: var(--pipes-muted);
            padding: 1rem;
        }
        .output {
            border-top: 1px solid var(--pipes-border);
            background: var(--pipes-surface);
            padding: 1rem;
        }
        .output pre {
            max-height: 18rem;
            overflow: auto;
            margin: 0.5rem 0 0;
            border: 1px solid var(--pipes-border);
            border-radius: var(--pipes-radius);
            background: var(--pipes-surface-alt);
            padding: 0.75rem;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            font-size: 0.82rem;
        }
        .field {
            display: grid;
            gap: 0.35rem;
            margin-bottom: 0.8rem;
        }
        .field label {
            color: var(--pipes-muted);
            font-size: 0.78rem;
            font-weight: 600;
        }
        .schema {
            border: 1px solid var(--pipes-border);
            border-radius: var(--pipes-radius);
            padding: 0.7rem;
            margin-bottom: 0.8rem;
        }
        .schema h3 {
            margin: 0 0 0.45rem;
            font-size: 0.88rem;
        }
        .schema-row {
            display: grid;
            gap: 0.15rem;
            padding: 0.45rem 0;
            border-top: 1px solid var(--pipes-border);
        }
        .schema-row:first-of-type { border-top: 0; }
        .input-target {
            width: 100%;
            text-align: left;
            border: 1px solid transparent;
            background: transparent;
            padding: 0.45rem;
        }
        .input-target.active {
            border-color: var(--pipes-accent);
            background: color-mix(in srgb, var(--pipes-accent) 10%, transparent);
        }
        .chip-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
            margin-top: 0.45rem;
        }
        .path-chip {
            max-width: 100%;
            border-radius: 999px;
            padding: 0.35rem 0.55rem;
            color: var(--pipes-link);
            overflow-wrap: anywhere;
            text-align: left;
        }
        .path-chip.bound {
            border-color: var(--pipes-accent);
            color: var(--pipes-accent);
            background: color-mix(in srgb, var(--pipes-accent) 8%, transparent);
        }
        .source-panel {
            border: 1px solid var(--pipes-border);
            border-radius: var(--pipes-radius);
            padding: 0.65rem;
            margin-bottom: 0.65rem;
        }
        .source-panel h4 {
            margin: 0 0 0.2rem;
            font-size: 0.84rem;
        }
        .binding {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.45rem;
            border: 1px solid var(--pipes-border);
            border-radius: var(--pipes-radius);
            padding: 0.6rem;
            margin-bottom: 0.55rem;
        }
        .binding .wide { grid-column: 1 / -1; }
        .output-setting {
            border: 1px solid var(--pipes-border);
            border-radius: var(--pipes-radius);
            margin-bottom: 0.55rem;
            padding: 0.65rem;
        }
        .output-setting label {
            align-items: center;
            color: var(--pipes-text);
            display: flex;
            font-size: 0.88rem;
            font-weight: 600;
            gap: 0.45rem;
            margin-bottom: 0.45rem;
        }
        .notice {
            color: var(--pipes-muted);
            font-size: 0.86rem;
        }
        .error {
            color: var(--pipes-danger);
        }
        @media (max-width: 1100px) {
            .app {
                grid-template-columns: 16rem minmax(22rem, 1fr);
            }
            .inspector {
                grid-column: 1 / -1;
                border-left: 0;
                border-top: 1px solid var(--pipes-border);
            }
        }
        @media (max-width: 760px) {
            .app {
                display: flex;
                flex-direction: column;
                min-height: 100vh;
            }
            .workspace {
                order: 1;
                min-height: 60vh;
            }
            .sidebar {
                order: 2;
                border-right: 0;
                border-top: 1px solid var(--pipes-border);
                border-bottom: 1px solid var(--pipes-border);
                max-height: 18rem;
                padding: 0.75rem;
            }
            .inspector {
                order: 3;
                border-left: 0;
                border-top: 1px solid var(--pipes-border);
                padding: 0.75rem;
            }
            .topbar, .title-row {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
            .topbar {
                padding: 0.75rem;
            }
            .actions {
                justify-content: stretch;
            }
            .actions button {
                flex: 1;
            }
            .brand {
                margin-bottom: 0.6rem;
            }
            .section-title {
                margin-top: 0.85rem;
            }
            .list {
                gap: 0.35rem;
            }
            .list-item {
                padding: 0.55rem;
            }
            .flow-step-actions {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .flow-step-actions button {
                min-width: 0;
                width: 100%;
            }
            .canvas {
                min-height: 22rem;
                padding: 0.75rem;
            }
            .graph {
                min-width: 34rem;
                min-height: 25rem;
            }
            .node {
                width: 14rem;
                min-height: 7rem;
                padding: 0.7rem;
            }
            .node-footer button {
                padding: 0.36rem 0.45rem;
            }
            .output {
                padding: 0.75rem;
            }
            .output pre {
                max-height: 12rem;
            }
            .binding {
                grid-template-columns: 1fr;
            }
            .binding .wide {
                grid-column: auto;
            }
        }
    </style>
</head>
<body>
    <?php wp_app_body_open(); ?>

    <div id="pipes-app" class="app">
        <aside class="sidebar">
            <h1 class="brand">Pipes</h1>
            <button class="primary" data-action="new-pipe">New Pipe</button>

            <h2 class="section-title">Saved</h2>
            <div class="list" data-pipes-list>
                <div class="notice">Loading pipes...</div>
            </div>

            <h2 class="section-title">Starters</h2>
            <div class="list" data-examples-list>
                <div class="notice">Loading starters...</div>
            </div>

            <h2 class="section-title">Abilities</h2>
            <input class="search" type="search" data-ability-search placeholder="Search abilities">
            <div class="list" data-abilities-list>
                <div class="notice">Loading abilities...</div>
            </div>
        </aside>

        <main class="workspace">
            <div class="topbar">
                <div class="title-row">
                    <input type="text" data-title value="Untitled Pipe" aria-label="Pipe title">
                    <span class="notice" data-status>Not saved</span>
                </div>
                <div class="actions">
                    <div class="mode-toggle" aria-label="Builder mode">
                        <button data-builder-mode="visual">Visual</button>
                        <button data-builder-mode="list">List</button>
                    </div>
                    <button data-action="save">Save</button>
                    <button data-action="run">Run</button>
                    <button class="danger" data-action="delete">Delete</button>
                </div>
            </div>

            <section class="list-builder" data-list-builder></section>

            <section class="canvas">
                <div class="graph" data-graph>
                    <svg class="edges" data-edges></svg>
                    <div class="empty" data-empty>Add abilities from the ability tray to build a flow.</div>
                </div>
            </section>

            <section class="output">
                <strong>Run Output</strong>
                <pre data-output>{}</pre>
            </section>
        </main>

        <aside class="inspector" data-inspector>
            <div class="empty">Select a node to configure inputs and bindings.</div>
        </aside>
    </div>

    <script>
    (() => {
        const config = {
            restUrl: <?php echo wp_json_encode( esc_url_raw( rest_url( 'pipes/v1/' ) ) ); ?>,
            nonce: <?php echo wp_json_encode( wp_create_nonce( 'wp_rest' ) ); ?>
        };

        const state = {
            abilities: [],
            examples: [],
            pipes: [],
            selectedPipeId: null,
            selectedNodeId: null,
            search: '',
            title: 'Untitled Pipe',
            graph: { nodes: [], edges: [] },
            lastRunResults: {},
            activeBindingTarget: '',
            builderMode: window.matchMedia('(max-width: 760px)').matches ? 'list' : 'visual',
            listAddOpen: false,
            listAddSearch: '',
            dirty: false
        };

        const $ = (selector, root = document) => root.querySelector(selector);
        const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));
        const selectorEscape = (value) => {
            if (window.CSS && typeof window.CSS.escape === 'function') {
                return window.CSS.escape(String(value));
            }
            return String(value).replace(/["\\]/g, '\\$&');
        };

        const request = async (path, options = {}) => {
            const response = await fetch(config.restUrl + path, {
                ...options,
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': config.nonce,
                    ...(options.headers || {})
                }
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(data.message || data?.data?.message || 'Request failed');
            }
            return data;
        };

        const setStatus = (message, isError = false) => {
            const status = $('[data-status]');
            status.textContent = message;
            status.classList.toggle('error', isError);
        };

        const markDirty = () => {
            state.dirty = true;
            setStatus('Unsaved changes');
        };

        const setPipeUrl = (pipeId = null) => {
            const url = new URL(window.location.href);
            if (pipeId) {
                url.searchParams.set('pipe', String(pipeId));
            } else {
                url.searchParams.delete('pipe');
            }
            window.history.replaceState({}, '', url);
        };

        const setBuilderMode = (mode) => {
            state.builderMode = mode === 'list' ? 'list' : 'visual';
            renderBuilderMode();
        };

        const renderBuilderMode = () => {
            $('.workspace').classList.toggle('builder-list', state.builderMode === 'list');
            $$('[data-builder-mode]').forEach((button) => {
                button.classList.toggle('active', button.dataset.builderMode === state.builderMode);
            });
        };

        const abilityById = (id) => state.abilities.find((ability) => ability.id === id);
        const nodeById = (id) => state.graph.nodes.find((node) => node.id === id);

        const selectedNode = () => nodeById(state.selectedNodeId);

        const schemaProperties = (schema) => {
            if (!schema || !schema.properties || typeof schema.properties !== 'object') {
                return [];
            }
            const required = Array.isArray(schema.required) ? schema.required : [];
            return Object.entries(schema.properties).map(([name, details]) => ({
                name,
                type: Array.isArray(details?.type) ? details.type.join('|') : (details?.type || 'any'),
                description: details?.description || '',
                required: required.includes(name)
            }));
        };

        const valueType = (value) => {
            if (Array.isArray(value)) {
                return 'array';
            }
            if (value === null) {
                return 'null';
            }
            if (Number.isInteger(value)) {
                return 'integer';
            }
            return typeof value;
        };

        const typeList = (type) => String(type || 'any').split('|').filter(Boolean);

        const typesCompatible = (inputType, outputType) => {
            const inputTypes = typeList(inputType);
            const outputTypes = typeList(outputType);
            if (!inputTypes.length || inputTypes.includes('any') || !outputTypes.length || outputTypes.includes('any')) {
                return true;
            }
            return inputTypes.some((input) => outputTypes.some((output) => (
                input === output ||
                (input === 'number' && output === 'integer') ||
                (input === 'object' && output === 'array')
            )));
        };

        const inputPortsForNode = (node) => {
            const ability = abilityById(node.ability_id);
            const props = schemaProperties(ability?.input_schema);
            const ports = props.length ?
                props.slice(0, 6).map((prop) => ({ name: prop.name, label: prop.name })) :
                Object.keys(node.args || {}).slice(0, 6).map((key) => ({ name: key, label: key }));
            for (const binding of node.bindings || []) {
                if (binding.target && !ports.some((port) => port.name === binding.target)) {
                    ports.push({ name: binding.target, label: binding.target });
                }
            }
            return ports;
        };

        const outputPortsForNode = (node) => {
            const runResult = state.lastRunResults[node.id]?.result;
            let ports = [];
            if (runResult !== undefined) {
                ports = flattenPaths(runResult)
                    .filter((item) => item.path)
                    .slice(0, 6)
                    .map((item) => ({
                        path: item.path,
                        label: item.path,
                        value: formatPathValue(item.value),
                        type: valueType(item.value)
                    }));
            } else {
                const ability = abilityById(node.ability_id);
                const props = schemaProperties(ability?.output_schema);
                ports = props.length ?
                    props.slice(0, 6).map((prop) => ({ path: prop.name, label: prop.name, value: prop.type, type: prop.type })) :
                    [{ path: '', label: 'result', value: '', type: 'any' }];
            }
            for (const targetNode of state.graph.nodes) {
                for (const binding of targetNode.bindings || []) {
                    if (binding.source === node.id && !ports.some((port) => port.path === (binding.path || ''))) {
                        ports.push({ path: binding.path || '', label: binding.path || 'result', value: 'bound', type: 'any' });
                    }
                }
            }
            return ports;
        };

        const nodeHasOutput = (node) => {
            if (!node) {
                return false;
            }
            if (state.lastRunResults[node.id]?.result !== undefined) {
                return true;
            }
            const ability = abilityById(node.ability_id);
            if (schemaProperties(ability?.output_schema).length) {
                return true;
            }
            return state.graph.nodes.some((targetNode) => (
                targetNode.id !== node.id &&
                (targetNode.bindings || []).some((binding) => binding.source === node.id)
            ));
        };

        const bindingSourceNodesFor = (node) => {
            const nodeIndex = state.graph.nodes.findIndex((candidate) => candidate.id === node.id);
            return state.graph.nodes.filter((candidate, index) => (
                candidate.id !== node.id &&
                (nodeIndex < 0 || index < nodeIndex) &&
                nodeHasOutput(candidate)
            ));
        };

        const compatibleOutputPortsFor = (source, inputProp) => (
            outputPortsForNode(source).filter((port) => typesCompatible(inputProp?.type || 'any', port.type || 'any'))
        );

        const compatibleBindingSourceNodesFor = (node, inputProp) => (
            bindingSourceNodesFor(node).filter((source) => compatibleOutputPortsFor(source, inputProp).length)
        );

        const defaultArgsForAbility = (ability) => {
            const args = {};
            for (const prop of schemaProperties(ability.input_schema)) {
                if (prop.required) {
                    args[prop.name] = '';
                }
            }
            return args;
        };

        const flattenPaths = (value, prefix = '', paths = []) => {
            if (paths.length >= 40) {
                return paths;
            }
            if (value === null || typeof value !== 'object') {
                if (prefix) {
                    paths.push({ path: prefix, value });
                }
                return paths;
            }
            if (Array.isArray(value)) {
                if (prefix) {
                    paths.push({ path: prefix, value });
                }
                value.slice(0, 3).forEach((item, index) => {
                    flattenPaths(item, prefix ? `${prefix}.${index}` : `${index}`, paths);
                });
                return paths;
            }
            if (prefix) {
                paths.push({ path: prefix, value });
            }
            Object.entries(value).slice(0, 16).forEach(([key, item]) => {
                flattenPaths(item, prefix ? `${prefix}.${key}` : key, paths);
            });
            return paths;
        };

        const formatPathValue = (value) => {
            if (value === null) {
                return 'null';
            }
            if (Array.isArray(value)) {
                return `${value.length} items`;
            }
            if (typeof value === 'object') {
                return 'object';
            }
            const text = String(value);
            return text.length > 44 ? `${text.slice(0, 41)}...` : text;
        };

        const ensureActiveBindingTarget = (node, props) => {
            if (!node) {
                state.activeBindingTarget = '';
                return '';
            }
            const names = props.map((prop) => prop.name);
            if (state.activeBindingTarget && (!names.length || names.includes(state.activeBindingTarget))) {
                return state.activeBindingTarget;
            }
            state.activeBindingTarget = names[0] || '';
            return state.activeBindingTarget;
        };

        const bindPathToActiveInput = (sourceId, path) => {
            const node = selectedNode();
            if (!node || !state.activeBindingTarget) {
                setStatus('Select an input before choosing an output path.', true);
                return;
            }
            node.bindings = node.bindings || [];
            const existing = node.bindings.find((binding) => binding.target === state.activeBindingTarget);
            if (existing) {
                existing.source = sourceId;
                existing.path = path;
            } else {
                node.bindings.push({ target: state.activeBindingTarget, source: sourceId, path });
            }
            node.args = node.args || {};
            delete node.args[state.activeBindingTarget];
            syncEdgesFromBindings();
            markDirty();
            render();
        };

        const defaultBindingForNode = (node, props) => {
            const source = bindingSourceNodesFor(node)[0];
            return {
                target: props[0]?.name || '',
                source: source?.id || '',
                path: ''
            };
        };

        const formatArgValue = (value) => {
            if (value === undefined || value === null) {
                return '';
            }
            if (typeof value === 'object') {
                return JSON.stringify(value, null, 2);
            }
            return String(value);
        };

        const parseArgValue = (prop, raw, checked = false) => {
            if (prop.type.includes('boolean')) {
                return checked;
            }
            if (raw === '') {
                return undefined;
            }
            if (prop.type.includes('integer')) {
                return Number.parseInt(raw, 10);
            }
            if (prop.type.includes('number')) {
                return Number.parseFloat(raw);
            }
            if (prop.type.includes('array') || prop.type.includes('object')) {
                return JSON.parse(raw);
            }
            return raw;
        };

        const attachNodeDrag = (element, node) => {
            let drag = null;
            element.addEventListener('pointerdown', (event) => {
                if (event.button !== 0 || event.target.closest('button, input, textarea, select, a')) {
                    return;
                }
                event.preventDefault();
                state.selectedNodeId = node.id;
                drag = {
                    pointerId: event.pointerId,
                    startX: event.clientX,
                    startY: event.clientY,
                    nodeX: node.position.x,
                    nodeY: node.position.y,
                    moved: false
                };
                element.classList.add('dragging');
                element.setPointerCapture(event.pointerId);
                renderInspector();
            });
            element.addEventListener('pointermove', (event) => {
                if (!drag || drag.pointerId !== event.pointerId) {
                    return;
                }
                event.preventDefault();
                const dx = event.clientX - drag.startX;
                const dy = event.clientY - drag.startY;
                if (Math.abs(dx) + Math.abs(dy) > 3) {
                    drag.moved = true;
                    element.dataset.dragged = 'true';
                }
                node.position.x = Math.max(16, Math.round(drag.nodeX + dx));
                node.position.y = Math.max(16, Math.round(drag.nodeY + dy));
                element.style.left = `${node.position.x}px`;
                element.style.top = `${node.position.y}px`;
                renderEdges();
            });
            const finish = (event) => {
                if (!drag || drag.pointerId !== event.pointerId) {
                    return;
                }
                event.preventDefault();
                element.classList.remove('dragging');
                element.releasePointerCapture(event.pointerId);
                if (drag.moved) {
                    markDirty();
                    render();
                }
                drag = null;
                window.setTimeout(() => {
                    delete element.dataset.dragged;
                }, 0);
            };
            element.addEventListener('pointerup', finish);
            element.addEventListener('pointercancel', finish);
        };

        const addNode = (ability) => {
            const index = state.graph.nodes.length;
            const id = `node-${Date.now().toString(36)}-${index}`;
            const previous = state.graph.nodes[index - 1];
            const mobile = window.matchMedia('(max-width: 760px)').matches;
            state.graph.nodes.push({
                id,
                ability_id: ability.id,
                label: ability.label,
                args: defaultArgsForAbility(ability),
                bindings: [],
                position: mobile ? { x: 36, y: 42 + index * 245 } : { x: 28 + index * 320, y: 42 + (index % 3) * 48 }
            });
            if (previous) {
                state.graph.edges.push({ from: previous.id, to: id });
            }
            state.selectedNodeId = id;
            markDirty();
            render();
        };

        const newPipe = () => {
            state.selectedPipeId = null;
            state.selectedNodeId = null;
            state.title = 'Untitled Pipe';
            state.graph = { nodes: [], edges: [] };
            state.lastRunResults = {};
            state.activeBindingTarget = '';
            state.listAddOpen = false;
            state.listAddSearch = '';
            state.dirty = false;
            $('[data-title]').value = state.title;
            $('[data-output]').textContent = '{}';
            setPipeUrl();
            setStatus('Not saved');
            render();
        };

        const loadExample = async (example) => {
            setStatus('Loading starter...');
            const data = await request(`examples/${example.id}`, { method: 'POST' });
            state.selectedPipeId = data.pipe.id;
            state.selectedNodeId = data.pipe.graph?.nodes?.[0]?.id || null;
            state.title = data.pipe.title || example.title || 'Untitled Pipe';
            state.graph = data.pipe.graph || { nodes: [], edges: [] };
            state.lastRunResults = {};
            state.activeBindingTarget = '';
            state.listAddOpen = false;
            state.listAddSearch = '';
            state.dirty = false;
            $('[data-title]').value = state.title;
            $('[data-output]').textContent = '{}';
            setPipeUrl(state.selectedPipeId);
            setStatus('Starter pipe loaded');
            await loadPipes();
            await loadExamples();
            render();
        };

        const loadPipe = async (id, updateUrl = true) => {
            const data = await request(`pipes/${id}`);
            state.selectedPipeId = data.pipe.id;
            state.title = data.pipe.title;
            state.graph = data.pipe.graph || { nodes: [], edges: [] };
            state.selectedNodeId = state.graph.nodes[0]?.id || null;
            state.lastRunResults = {};
            state.activeBindingTarget = '';
            state.listAddOpen = false;
            state.listAddSearch = '';
            state.dirty = false;
            $('[data-title]').value = state.title;
            $('[data-output]').textContent = '{}';
            if (updateUrl) {
                setPipeUrl(state.selectedPipeId);
            }
            setStatus('Saved');
            render();
        };

        const savePipe = async () => {
            state.title = $('[data-title]').value.trim() || 'Untitled Pipe';
            const path = state.selectedPipeId ? `pipes/${state.selectedPipeId}` : 'pipes';
            const data = await request(path, {
                method: 'POST',
                body: JSON.stringify({ title: state.title, graph: state.graph })
            });
            state.selectedPipeId = data.pipe.id;
            state.title = data.pipe.title;
            state.graph = data.pipe.graph;
            state.dirty = false;
            $('[data-title]').value = state.title;
            setPipeUrl(state.selectedPipeId);
            setStatus('Saved');
            await loadPipes();
            render();
        };

        const deletePipe = async () => {
            if (!state.selectedPipeId) {
                newPipe();
                return;
            }
            if (!window.confirm('Delete this pipe?')) {
                return;
            }
            await request(`pipes/${state.selectedPipeId}`, { method: 'DELETE' });
            await loadPipes();
            setPipeUrl();
            newPipe();
        };

        const runPipe = async () => {
            setStatus('Running...');
            const data = await request('run', {
                method: 'POST',
                body: JSON.stringify({
                    pipe_id: state.selectedPipeId || 0,
                    graph: state.graph,
                    confirm_destructive: $('[data-confirm-destructive]')?.checked || false
                })
            });
            state.lastRunResults = data.results || {};
            $('[data-output]').textContent = JSON.stringify(data, null, 2);
            setStatus(state.dirty ? 'Unsaved changes' : 'Run complete');
            render();
        };

        const loadPipes = async () => {
            const data = await request('pipes');
            state.pipes = data.pipes || [];
            renderPipes();
        };

        const loadAbilities = async () => {
            const data = await request('abilities');
            state.abilities = data.abilities || [];
            renderAbilities();
        };

        const loadExamples = async () => {
            const data = await request('examples');
            state.examples = data.examples || [];
            renderExamples();
        };

        const renderPipes = () => {
            const list = $('[data-pipes-list]');
            if (!state.pipes.length) {
                list.innerHTML = '<div class="notice">No saved pipes yet.</div>';
                return;
            }
            list.innerHTML = '';
            for (const pipe of state.pipes) {
                const button = document.createElement('button');
                button.className = `list-item ${pipe.id === state.selectedPipeId ? 'active' : ''}`;
                button.innerHTML = `<strong></strong><span class="meta"></span>`;
                $('strong', button).textContent = pipe.title;
                $('.meta', button).textContent = new Date(pipe.modified).toLocaleString();
                button.addEventListener('click', () => loadPipe(pipe.id).catch((error) => setStatus(error.message, true)));
                list.append(button);
            }
        };

        const renderAbilities = () => {
            const list = $('[data-abilities-list]');
            const query = state.search.toLowerCase();
            const abilities = state.abilities.filter((ability) => {
                const haystack = `${ability.label} ${ability.id} ${ability.category} ${ability.description}`.toLowerCase();
                return haystack.includes(query);
            });
            if (!abilities.length) {
                list.innerHTML = '<div class="notice">No matching abilities.</div>';
                return;
            }
            list.innerHTML = '';
            for (const ability of abilities) {
                const button = document.createElement('button');
                button.className = 'list-item';
                button.innerHTML = `
                    <strong></strong>
                    <span class="meta"></span>
                    <span class="badge-row"></span>
                `;
                $('strong', button).textContent = ability.label;
                $('.meta', button).textContent = ability.id;
                const badges = $('.badge-row', button);
                if (ability.category) {
                    badges.append(badge(ability.category));
                }
                if (ability.readonly) {
                    badges.append(badge('read-only'));
                }
                if (ability.destructive) {
                    badges.append(badge('destructive', 'warn'));
                }
                button.addEventListener('click', () => addNode(ability));
                list.append(button);
            }
        };

        const renderExamples = () => {
            const list = $('[data-examples-list]');
            if (!state.examples.length) {
                list.innerHTML = '<div class="notice">No starters available for the active abilities.</div>';
                return;
            }
            list.innerHTML = '';
            for (const example of state.examples) {
                const button = document.createElement('button');
                button.className = 'list-item';
                button.innerHTML = `
                    <strong></strong>
                    <span class="meta"></span>
                    <span class="badge-row"></span>
                `;
                $('strong', button).textContent = example.title;
                $('.meta', button).textContent = example.description || '';
                $('.badge-row', button).append(badge(`${example.graph?.nodes?.length || 0} nodes`));
                if (example.pipe_id) {
                    $('.badge-row', button).append(badge('saved'));
                }
                button.addEventListener('click', () => loadExample(example).catch((error) => setStatus(error.message, true)));
                list.append(button);
            }
        };

        const badge = (text, className = '') => {
            const span = document.createElement('span');
            span.className = `badge ${className}`;
            span.textContent = text;
            return span;
        };

        const renderGraph = () => {
            const graph = $('[data-graph]');
            $$('.node', graph).forEach((node) => node.remove());
            $('[data-empty]').style.display = state.graph.nodes.length ? 'none' : 'block';

            for (const node of state.graph.nodes) {
                const ability = abilityById(node.ability_id);
                const element = document.createElement('article');
                element.className = `node ${node.id === state.selectedNodeId ? 'selected' : ''}`;
                element.style.left = `${node.position.x}px`;
                element.style.top = `${node.position.y}px`;
                element.innerHTML = `
                    <h2></h2>
                    <div class="node-id"></div>
                    <p class="meta"></p>
                    <div class="badge-row"></div>
                    <div class="port-groups">
                        <div>
                            <div class="port-group-title">Inputs</div>
                            <div data-input-ports></div>
                        </div>
                        <div>
                            <div class="port-group-title">Outputs</div>
                            <div data-output-ports></div>
                        </div>
                    </div>
                    <div class="node-footer">
                        <button data-move="-1">Left</button>
                        <button data-move="1">Right</button>
                    </div>
                `;
                $('h2', element).textContent = node.label || ability?.label || node.ability_id;
                $('.node-id', element).textContent = node.id;
                $('p', element).textContent = ability?.description || '';
                const badges = $('.badge-row', element);
                if (ability?.readonly) {
                    badges.append(badge('read-only'));
                }
                if (ability?.destructive) {
                    badges.append(badge('destructive', 'warn'));
                }
                if ((node.bindings || []).length) {
                    badges.append(badge(`${node.bindings.length} bound`));
                }
                if (state.lastRunResults[node.id]) {
                    badges.append(badge('has output'));
                }
                const inputPorts = $('[data-input-ports]', element);
                for (const port of inputPortsForNode(node)) {
                    const input = document.createElement('button');
                    input.type = 'button';
                    input.className = 'port input-port';
                    input.dataset.portKind = 'input';
                    input.dataset.nodeId = node.id;
                    input.dataset.portName = port.name;
                    if ((node.bindings || []).some((binding) => binding.target === port.name)) {
                        input.classList.add('bound');
                    }
                    if (node.id === state.selectedNodeId && state.activeBindingTarget === port.name) {
                        input.classList.add('active');
                    }
                    input.textContent = port.label;
                    input.addEventListener('click', (event) => {
                        event.stopPropagation();
                        state.selectedNodeId = node.id;
                        state.activeBindingTarget = port.name;
                        render();
                    });
                    inputPorts.append(input);
                }
                const outputPorts = $('[data-output-ports]', element);
                for (const port of outputPortsForNode(node)) {
                    const output = document.createElement('button');
                    output.type = 'button';
                    output.className = 'port output-port';
                    output.dataset.portKind = 'output';
                    output.dataset.nodeId = node.id;
                    output.dataset.portPath = port.path;
                    if (state.graph.nodes.some((targetNode) => (targetNode.bindings || []).some((binding) => binding.source === node.id && (binding.path || '') === port.path))) {
                        output.classList.add('bound');
                    }
                    output.title = port.value ? `${port.label}: ${port.value}` : port.label;
                    output.textContent = port.label;
                    output.addEventListener('click', (event) => {
                        event.stopPropagation();
                        bindPathToActiveInput(node.id, port.path);
                    });
                    outputPorts.append(output);
                }
                element.addEventListener('click', (event) => {
                    if (element.dataset.dragged === 'true') {
                        event.preventDefault();
                        return;
                    }
                    if (event.target.matches('[data-move]')) {
                        const direction = Number(event.target.dataset.move);
                        node.position.x = Math.max(28, node.position.x + direction * 40);
                        markDirty();
                    } else {
                        state.selectedNodeId = node.id;
                    }
                    render();
                });
                attachNodeDrag(element, node);
                graph.append(element);
            }

            renderEdges();
        };

        const renderListBuilder = () => {
            const container = $('[data-list-builder]');
            if (!state.graph.nodes.length) {
                container.innerHTML = '<div class="empty">Add abilities from the ability tray to build a flow.</div>';
                return;
            }

            container.innerHTML = '<div class="flow-list" data-flow-list></div>';
            const list = $('[data-flow-list]', container);
            state.graph.nodes.forEach((node, index) => {
                const ability = abilityById(node.ability_id);
                const step = document.createElement('article');
                step.className = `flow-step ${node.id === state.selectedNodeId ? 'active' : ''}`;
                step.innerHTML = `
                    <div class="flow-step-header">
                        <div class="flow-step-number"></div>
                        <div class="flow-step-main">
                            <input class="flow-step-title" type="text" data-step-label>
                            <div class="meta"></div>
                            <div class="badge-row"></div>
                        </div>
                    </div>
                    <div class="flow-step-actions">
                        <button data-step-action="up">Up</button>
                        <button data-step-action="down">Down</button>
                        <button class="danger" data-step-action="remove">Remove</button>
                    </div>
                    <div class="list-args" data-list-args></div>
                `;
                $('.flow-step-number', step).textContent = String(index + 1);
                $('[data-step-label]', step).value = node.label || ability?.label || node.ability_id;
                $('.meta', step).textContent = node.ability_id;
                const props = schemaProperties(ability?.input_schema);
                const badges = $('.badge-row', step);
                if (ability?.category) {
                    badges.append(badge(ability.category));
                }
                if ((node.bindings || []).length) {
                    badges.append(badge(`${node.bindings.length} ${node.bindings.length === 1 ? 'binding' : 'bindings'}`));
                }
                if (state.lastRunResults[node.id]) {
                    badges.append(badge('has output'));
                }
                renderListArgs(node, props, $('[data-list-args]', step));
                $('[data-step-label]', step).addEventListener('input', (event) => {
                    state.selectedNodeId = node.id;
                    node.label = event.target.value;
                    markDirty();
                    renderGraph();
                    renderInspector();
                });
                $('[data-step-label]', step).addEventListener('blur', () => {
                    if (!node.label.trim()) {
                        node.label = ability?.label || node.ability_id;
                        render();
                    }
                });
                step.addEventListener('click', (event) => {
                    const action = event.target.closest('[data-step-action]')?.dataset.stepAction;
                    if (!action && event.target.closest('input, select, textarea')) {
                        state.selectedNodeId = node.id;
                        return;
                    }
                    if (!action) {
                        state.selectedNodeId = node.id;
                        render();
                        return;
                    }
                    if (action === 'up') {
                        moveNode(index, -1);
                    } else if (action === 'down') {
                        moveNode(index, 1);
                    } else if (action === 'remove') {
                        removeNode(node.id);
                    }
                    render();
                });
                list.append(step);
            });
            renderListAddNode(list);
        };

        const renderListAddNode = (list) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'flow-add';
            if (!state.listAddOpen) {
                wrapper.innerHTML = '<button class="flow-add-button" data-list-add-open><strong>+</strong><span>Add next node</span></button>';
                $('[data-list-add-open]', wrapper).addEventListener('click', () => {
                    state.listAddOpen = true;
                    renderListBuilder();
                    $('[data-list-add-search]')?.focus();
                });
                list.append(wrapper);
                return;
            }

            wrapper.innerHTML = `
                <div class="flow-add-picker">
                    <input type="search" data-list-add-search placeholder="Search abilities">
                    <div class="flow-add-results" data-list-add-results></div>
                    <button data-list-add-cancel>Cancel</button>
                </div>
            `;
            const search = $('[data-list-add-search]', wrapper);
            const results = $('[data-list-add-results]', wrapper);
            search.value = state.listAddSearch;
            const renderResults = () => {
                const query = state.listAddSearch.toLowerCase();
                const abilities = state.abilities.filter((ability) => {
                    const haystack = `${ability.label} ${ability.id} ${ability.category} ${ability.description}`.toLowerCase();
                    return haystack.includes(query);
                }).slice(0, 12);
                results.innerHTML = abilities.length ? '' : '<div class="notice">No matching abilities.</div>';
                for (const ability of abilities) {
                    const button = document.createElement('button');
                    button.className = 'list-item';
                    button.innerHTML = '<strong></strong><span class="meta"></span>';
                    $('strong', button).textContent = ability.label;
                    $('.meta', button).textContent = ability.id;
                    button.addEventListener('click', () => {
                        state.listAddOpen = false;
                        state.listAddSearch = '';
                        addNode(ability);
                    });
                    results.append(button);
                }
            };
            search.addEventListener('input', (event) => {
                state.listAddSearch = event.target.value;
                renderResults();
            });
            $('[data-list-add-cancel]', wrapper).addEventListener('click', () => {
                state.listAddOpen = false;
                state.listAddSearch = '';
                renderListBuilder();
            });
            renderResults();
            list.append(wrapper);
        };

        const moveNode = (index, direction) => {
            const target = index + direction;
            if (target < 0 || target >= state.graph.nodes.length) {
                return;
            }
            const nodes = state.graph.nodes;
            [nodes[index], nodes[target]] = [nodes[target], nodes[index]];
            nodes.forEach((node, nextIndex) => {
                node.position = window.matchMedia('(max-width: 760px)').matches ?
                    { x: 36, y: 42 + nextIndex * 245 } :
                    { ...node.position, x: 28 + nextIndex * 320 };
            });
            syncEdgesFromBindings();
            markDirty();
        };

        const removeNode = (nodeId) => {
            state.graph.nodes = state.graph.nodes.filter((candidate) => candidate.id !== nodeId);
            state.graph.edges = state.graph.edges.filter((edge) => edge.from !== nodeId && edge.to !== nodeId);
            for (const node of state.graph.nodes) {
                node.bindings = (node.bindings || []).filter((binding) => binding.source !== nodeId);
            }
            state.selectedNodeId = state.graph.nodes[0]?.id || null;
            markDirty();
        };

        const renderEdges = () => {
            const svg = $('[data-edges]');
            svg.innerHTML = '';
            const maxX = Math.max(1200, ...state.graph.nodes.map((node) => (node.position?.x || 0) + 360));
            const maxY = Math.max(720, ...state.graph.nodes.map((node) => (node.position?.y || 0) + 260));
            $('[data-graph]').style.width = `${maxX}px`;
            $('[data-graph]').style.height = `${maxY}px`;
            svg.setAttribute('width', String(maxX));
            svg.setAttribute('height', String(maxY));
            const bindings = graphBindings();
            let rendered = 0;
            bindings.forEach((binding, index) => {
                binding.index = index;
                rendered += renderBindingEdge(svg, binding, index, bindings);
            });
            if (rendered) {
                return;
            }
            for (const edge of state.graph.edges) {
                rendered += renderNodeEdge(svg, edge.from, edge.to);
            }
        };

        const graphBindings = () => {
            const bindings = [];
            for (const node of state.graph.nodes) {
                for (const binding of node.bindings || []) {
                    if (!binding.source || binding.source === node.id) {
                        continue;
                    }
                    bindings.push({
                        source: binding.source,
                        path: binding.path || '',
                        targetNode: node.id,
                        target: binding.target || ''
                    });
                }
            }
            return bindings;
        };

        const renderBindingEdge = (svg, binding, index, allBindings) => {
            const sourcePort = findOutputPort(binding.source, binding.path);
            const targetPort = findInputPort(binding.targetNode, binding.target);
            if (!sourcePort || !targetPort) {
                return renderNodeEdge(svg, binding.source, binding.targetNode, true);
            }
            const siblings = allBindings.filter((candidate) => candidate.source === binding.source && candidate.targetNode === binding.targetNode);
            const siblingIndex = siblings.findIndex((candidate) => candidate.index === binding.index);
            const siblingOffset = (siblingIndex - (siblings.length - 1) / 2) * 18;
            drawEdge(
                svg,
                portPoint(sourcePort, 'output'),
                portPoint(targetPort, 'input'),
                true,
                wireColor(index),
                siblingOffset
            );
            return 1;
        };

        const renderNodeEdge = (svg, fromId, toId, soft = false) => {
            const from = nodeById(fromId);
            const to = nodeById(toId);
            if (!from || !to) {
                return 0;
            }
            drawEdge(
                svg,
                { x: from.position.x + 288, y: from.position.y + 64 },
                { x: to.position.x, y: to.position.y + 64 },
                !soft,
                '',
                0
            );
            return 1;
        };

        const findInputPort = (nodeId, targetName) => {
            return $(`.port[data-port-kind="input"][data-node-id="${selectorEscape(nodeId)}"][data-port-name="${selectorEscape(targetName)}"]`);
        };

        const findOutputPort = (nodeId, sourcePath) => {
            return $(`.port[data-port-kind="output"][data-node-id="${selectorEscape(nodeId)}"][data-port-path="${selectorEscape(sourcePath)}"]`) ||
                $(`.port[data-port-kind="output"][data-node-id="${selectorEscape(nodeId)}"]`);
        };

        const portPoint = (element, kind) => {
            const graphRect = $('[data-graph]').getBoundingClientRect();
            const rect = element.getBoundingClientRect();
            const dotOffset = 13;
            return {
                x: (kind === 'output' ? rect.right + dotOffset : rect.left - dotOffset) - graphRect.left,
                y: rect.top + rect.height / 2 - graphRect.top
            };
        };

        const wireColor = (index) => {
            const colors = ['#147d64', '#2368cc', '#b54708', '#7a5af8', '#c11574', '#088ab2'];
            return colors[index % colors.length];
        };

        const drawEdge = (svg, from, to, strong, color = '', offset = 0) => {
            const mid = Math.max(44, Math.abs(to.x - from.x) / 2);
            const fromY = from.y + offset;
            const toY = to.y + offset;
            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('d', `M ${from.x} ${from.y} C ${from.x + mid} ${fromY}, ${to.x - mid} ${toY}, ${to.x} ${to.y}`);
            path.setAttribute('fill', 'none');
            path.setAttribute('stroke', strong ? (color || 'var(--pipes-accent)') : 'color-mix(in srgb, var(--pipes-muted) 55%, transparent)');
            path.setAttribute('stroke-width', strong ? '3' : '1.5');
            path.setAttribute('stroke-linecap', 'round');
            path.setAttribute('stroke-linejoin', 'round');
            if (!strong) {
                path.setAttribute('stroke-dasharray', '5 7');
            }
            svg.append(path);
            if (strong) {
                drawEndpoint(svg, from, color);
                drawEndpoint(svg, to, color);
            }
        };

        const drawEndpoint = (svg, point, color = '') => {
            const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            circle.setAttribute('cx', String(point.x));
            circle.setAttribute('cy', String(point.y));
            circle.setAttribute('r', '4.5');
            circle.setAttribute('fill', 'var(--pipes-surface)');
            circle.setAttribute('stroke', color || 'var(--pipes-accent)');
            circle.setAttribute('stroke-width', '2.5');
            svg.append(circle);
        };

        const renderInspector = () => {
            const inspector = $('[data-inspector]');
            const node = nodeById(state.selectedNodeId);
            if (!node) {
                inspector.innerHTML = `
                    <div class="empty">Select a node to configure inputs and bindings.</div>
                `;
                return;
            }
            const ability = abilityById(node.ability_id);
            const props = schemaProperties(ability?.input_schema);
            const activeTarget = ensureActiveBindingTarget(node, props);
            inspector.innerHTML = `
                <div class="field">
                    <label>Node label</label>
                    <input type="text" data-node-label>
                </div>
                <div class="schema">
                    <h3>Inputs</h3>
                    <div class="notice">Choose the input you want to fill, then choose an output path below.</div>
                    <div data-schema></div>
                </div>
                <div class="schema">
                    <h3>Available Outputs</h3>
                    <div data-output-paths></div>
                </div>
                <div class="field">
                    <label>Base args JSON</label>
                    <textarea data-node-args spellcheck="false"></textarea>
                </div>
                <div class="field">
                    <label><input type="checkbox" data-confirm-destructive> Confirm destructive abilities for run</label>
                </div>
                <div class="schema">
                    <h3>Bindings</h3>
                    <div data-bindings></div>
                    <button data-action="add-binding">Add Binding</button>
                </div>
                <button class="danger" data-action="remove-node">Remove Node</button>
            `;
            $('[data-node-label]', inspector).value = node.label || ability?.label || node.ability_id;
            $('[data-node-args]', inspector).value = JSON.stringify(node.args || {}, null, 2);

            const schema = $('[data-schema]', inspector);
            schema.innerHTML = props.length ? '' : '<div class="notice">This ability has no declared input schema.</div>';
            for (const prop of props) {
                const row = document.createElement('button');
                row.type = 'button';
                row.className = `input-target ${prop.name === activeTarget ? 'active' : ''}`;
                row.innerHTML = `<strong></strong><span class="meta"></span><span class="notice"></span><div class="badge-row"></div>`;
                $('strong', row).textContent = `${prop.name}${prop.required ? ' *' : ''}`;
                $('.meta', row).textContent = prop.type;
                $('.notice', row).textContent = prop.description;
                const binding = (node.bindings || []).find((candidate) => candidate.target === prop.name);
                if (binding) {
                    $('.badge-row', row).append(badge(`${binding.source}.${binding.path || '(whole output)'}`));
                } else if (Object.prototype.hasOwnProperty.call(node.args || {}, prop.name)) {
                    $('.badge-row', row).append(badge('base arg'));
                }
                row.addEventListener('click', () => {
                    state.activeBindingTarget = prop.name;
                    renderInspector();
                });
                schema.append(row);
            }

            $('[data-node-label]', inspector).addEventListener('input', (event) => {
                node.label = event.target.value;
                markDirty();
                renderGraph();
            });
            $('[data-node-args]', inspector).addEventListener('change', (event) => {
                try {
                    node.args = JSON.parse(event.target.value || '{}');
                    markDirty();
                    setStatus('Unsaved changes');
                } catch (error) {
                    setStatus('Invalid JSON in node args', true);
                }
            });
            $('[data-action="add-binding"]', inspector).addEventListener('click', () => {
                node.bindings = node.bindings || [];
                node.bindings.push(defaultBindingForNode(node, props));
                markDirty();
                renderInspector();
            });
            $('[data-action="remove-node"]', inspector).addEventListener('click', () => {
                removeNode(node.id);
                render();
            });

            renderOutputPaths(node);
            renderBindings(node, props);
        };

        const renderOutputPaths = (node) => {
            const container = $('[data-output-paths]');
            const inputProp = schemaProperties(abilityById(node.ability_id)?.input_schema)
                .find((prop) => prop.name === state.activeBindingTarget);
            const sourceNodes = compatibleBindingSourceNodesFor(node, inputProp);
            const panels = [];
            for (const source of sourceNodes) {
                const runResult = state.lastRunResults[source.id]?.result;
                const paths = runResult === undefined ? [] : flattenPaths(runResult)
                    .filter((item) => typesCompatible(inputProp?.type || 'any', valueType(item.value)));
                panels.push({ source, paths });
            }

            if (!panels.length) {
                container.innerHTML = '<div class="notice">Add an upstream node to create a binding source.</div>';
                return;
            }

            container.innerHTML = '';
            for (const panel of panels) {
                const section = document.createElement('div');
                section.className = 'source-panel';
                section.innerHTML = `<h4></h4><div class="meta"></div><div class="chip-row"></div>`;
                $('h4', section).textContent = panel.source.label || panel.source.ability_id;
                $('.meta', section).textContent = panel.paths.length ? 'Click a path to bind it to the selected input.' : 'Run the pipe to inspect real output paths, or use the manual bindings below.';
                const chips = $('.chip-row', section);
                for (const item of panel.paths.slice(0, 24)) {
                    const chip = document.createElement('button');
                    chip.type = 'button';
                    chip.className = 'path-chip';
                    const binding = (node.bindings || []).find((candidate) => (
                        candidate.target === state.activeBindingTarget &&
                        candidate.source === panel.source.id &&
                        candidate.path === item.path
                    ));
                    if (binding) {
                        chip.classList.add('bound');
                    }
                    chip.textContent = `${item.path} = ${formatPathValue(item.value)}`;
                    chip.addEventListener('click', () => bindPathToActiveInput(panel.source.id, item.path));
                    chips.append(chip);
                }
                container.append(section);
            }
        };

        const renderListArgs = (node, props, container) => {
            if (!container) {
                return;
            }
            if (!props.length) {
                container.innerHTML = '<div class="notice">No declared inputs.</div>';
                return;
            }

            container.innerHTML = '<strong>Inputs</strong>';
            node.args = node.args || {};
            for (const prop of props) {
                const binding = (node.bindings || []).find((candidate) => candidate.target === prop.name);
                const sourceNodes = compatibleBindingSourceNodesFor(node, prop);
                const row = document.createElement('div');
                row.className = 'list-arg';
                row.innerHTML = '<label></label>';
                $('label', row).textContent = `${prop.name}${prop.required ? ' *' : ''}`;

                const bindSelect = document.createElement('select');
                const manualOption = document.createElement('option');
                manualOption.value = '';
                manualOption.textContent = 'Manual value';
                bindSelect.append(manualOption);
                for (const source of sourceNodes) {
                    const option = document.createElement('option');
                    option.value = source.id;
                    option.textContent = source.label || source.ability_id;
                    bindSelect.append(option);
                }
                bindSelect.value = binding?.source || '';
                bindSelect.addEventListener('change', (event) => {
                    node.bindings = node.bindings || [];
                    const existing = node.bindings.find((candidate) => candidate.target === prop.name);
                    if (!event.target.value) {
                        node.bindings = node.bindings.filter((candidate) => candidate.target !== prop.name);
                        syncEdgesFromBindings();
                        markDirty();
                        render();
                        return;
                    }

                    const sourcePorts = compatibleOutputPortsFor(nodeById(event.target.value) || {}, prop);
                    const nextBinding = existing || { target: prop.name, source: event.target.value, path: sourcePorts[0]?.path || '' };
                    nextBinding.source = event.target.value;
                    nextBinding.path = sourcePorts.some((port) => port.path === nextBinding.path) ? nextBinding.path : (sourcePorts[0]?.path || '');
                    if (!existing) {
                        node.bindings.push(nextBinding);
                    }
                    delete node.args[prop.name];
                    syncEdgesFromBindings();
                    markDirty();
                    render();
                });
                row.append(bindSelect);

                if (binding) {
                    const source = nodeById(binding.source);
                    const pathSelect = document.createElement('select');
                    const ports = source ? compatibleOutputPortsFor(source, prop) : [];
                    if (!ports.some((port) => port.path === (binding.path || ''))) {
                        ports.unshift({ path: binding.path || '', label: binding.path || 'result', value: 'bound', type: 'any' });
                    }
                    for (const port of ports) {
                        const option = document.createElement('option');
                        option.value = port.path;
                        option.textContent = `${port.label}${port.value ? ` (${port.value})` : ''}`;
                        pathSelect.append(option);
                    }
                    pathSelect.value = binding.path || '';
                    pathSelect.addEventListener('change', (event) => {
                        binding.path = event.target.value;
                        syncEdgesFromBindings();
                        markDirty();
                        renderGraph();
                    });
                    row.append(pathSelect);
                    container.append(row);
                    continue;
                }

                const value = node.args[prop.name];
                const control = document.createElement(prop.type.includes('array') || prop.type.includes('object') ? 'textarea' : 'input');
                control.dataset.listArg = prop.name;
                if (prop.type.includes('boolean')) {
                    control.type = 'checkbox';
                    control.checked = !!value;
                } else if (prop.type.includes('integer') || prop.type.includes('number')) {
                    control.type = 'number';
                    control.value = formatArgValue(value);
                } else if (control.tagName !== 'TEXTAREA') {
                    control.type = 'text';
                    control.value = formatArgValue(value);
                } else {
                    control.value = formatArgValue(value);
                    control.spellcheck = false;
                }

                control.addEventListener(prop.type.includes('boolean') ? 'change' : 'input', (event) => {
                    try {
                        const nextValue = parseArgValue(prop, event.target.value, event.target.checked);
                        if (nextValue === undefined || Number.isNaN(nextValue)) {
                            delete node.args[prop.name];
                        } else {
                            node.args[prop.name] = nextValue;
                        }
                        markDirty();
                    } catch (error) {
                        setStatus(`Invalid ${prop.name} value`, true);
                    }
                });
                row.append(control);
                if (prop.description) {
                    const description = document.createElement('div');
                    description.className = 'meta';
                    description.textContent = prop.description;
                    row.append(description);
                }
                container.append(row);
            }
        };

        const renderBindings = (node, props, container = $('[data-bindings]')) => {
            if (!container) {
                return;
            }
            node.bindings = node.bindings || [];
            if (!node.bindings.length) {
                container.innerHTML = '<div class="notice">No bindings. Base args are passed directly.</div>';
                return;
            }
            container.innerHTML = '';
            node.bindings.forEach((binding, index) => {
                const targetProp = props.find((prop) => prop.name === (binding.target || props[0]?.name || ''));
                const sourceNodes = targetProp ? compatibleBindingSourceNodesFor(node, targetProp) : bindingSourceNodesFor(node);
                const element = document.createElement('div');
                element.className = 'binding';
                element.innerHTML = `
                    <div class="field">
                        <label>Target input</label>
                        <input type="text" data-binding-target>
                    </div>
                    <div class="field">
                        <label>Source node</label>
                        <select data-binding-source></select>
                    </div>
                    <div class="field wide">
                        <label>Source result path</label>
                        <input type="text" data-binding-path placeholder="items.0.id">
                    </div>
                    <button class="danger wide" data-remove-binding>Remove</button>
                `;
                const target = $('[data-binding-target]', element);
                target.value = binding.target || props[0]?.name || '';
                const select = $('[data-binding-source]', element);
                for (const source of sourceNodes) {
                    const option = document.createElement('option');
                    option.value = source.id;
                    option.textContent = source.label || source.ability_id;
                    select.append(option);
                }
                select.value = binding.source || sourceNodes[0]?.id || '';
                $('[data-binding-path]', element).value = binding.path || '';
                target.addEventListener('input', (event) => {
                    binding.target = event.target.value;
                    markDirty();
                });
                select.addEventListener('change', (event) => {
                    binding.source = event.target.value;
                    syncEdgesFromBindings();
                    markDirty();
                    render();
                });
                $('[data-binding-path]', element).addEventListener('input', (event) => {
                    binding.path = event.target.value;
                    markDirty();
                });
                $('[data-remove-binding]', element).addEventListener('click', () => {
                    node.bindings.splice(index, 1);
                    syncEdgesFromBindings();
                    markDirty();
                    render();
                });
                container.append(element);
            });
        };

        const syncEdgesFromBindings = () => {
            const edges = [];
            const seen = new Set();
            for (const node of state.graph.nodes) {
                for (const binding of node.bindings || []) {
                    if (!binding.source || binding.source === node.id) {
                        continue;
                    }
                    const key = `${binding.source}->${node.id}`;
                    if (!seen.has(key)) {
                        seen.add(key);
                        edges.push({ from: binding.source, to: node.id });
                    }
                }
            }
            if (!edges.length) {
                for (let i = 1; i < state.graph.nodes.length; i++) {
                    edges.push({ from: state.graph.nodes[i - 1].id, to: state.graph.nodes[i].id });
                }
            }
            state.graph.edges = edges;
        };

        const render = () => {
            renderPipes();
            renderExamples();
            renderAbilities();
            renderBuilderMode();
            renderGraph();
            renderListBuilder();
            renderInspector();
        };

        $('[data-title]').addEventListener('input', (event) => {
            state.title = event.target.value;
            markDirty();
        });
        $('[data-ability-search]').addEventListener('input', (event) => {
            state.search = event.target.value;
            renderAbilities();
        });
        $('[data-action="new-pipe"]').addEventListener('click', newPipe);
        $('[data-action="save"]').addEventListener('click', () => savePipe().catch((error) => setStatus(error.message, true)));
        $('[data-action="delete"]').addEventListener('click', () => deletePipe().catch((error) => setStatus(error.message, true)));
        $('[data-action="run"]').addEventListener('click', () => runPipe().catch((error) => setStatus(error.message, true)));
        $$('[data-builder-mode]').forEach((button) => {
            button.addEventListener('click', () => setBuilderMode(button.dataset.builderMode));
        });

        Promise.all([loadPipes(), loadAbilities(), loadExamples()])
            .then(() => {
                const pipeId = Number(new URLSearchParams(window.location.search).get('pipe') || 0);
                if (pipeId > 0) {
                    return loadPipe(pipeId, false);
                }
                render();
                return null;
            })
            .catch((error) => setStatus(error.message, true));
    })();
    </script>

    <?php wp_app_body_close(); ?>
</body>
</html>
