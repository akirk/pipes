<!DOCTYPE html>
<html <?php wp_app_language_attributes(); ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo wp_app_title( __( 'Pipes', 'pipes' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_app_title escapes. ?></title>
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
        input[type="checkbox"],
        input[type="radio"] {
            accent-color: var(--pipes-accent);
            background: transparent;
            border: 0;
            flex: 0 0 auto;
            height: auto;
            margin: 0;
            padding: 0;
            width: auto;
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
        .app.inspector-collapsed {
            grid-template-columns: 18rem minmax(24rem, 1fr);
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
        .app.inspector-collapsed .inspector {
            display: none;
        }
        .inspector-header {
            align-items: center;
            display: flex;
            gap: 0.5rem;
            justify-content: space-between;
            margin-bottom: 0.85rem;
        }
        .inspector-header strong {
            font-size: 0.86rem;
        }
        .inspector-close {
            align-items: center;
            display: inline-flex;
            flex: 0 0 auto;
            height: 1.8rem;
            justify-content: center;
            line-height: 1;
            padding: 0;
            width: 1.8rem;
        }
        .workspace {
            min-width: 0;
            display: grid;
            grid-template-rows: auto auto 1fr auto;
            min-height: 0;
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
        .workspace-message {
            border-bottom: 1px solid var(--pipes-border);
            background: color-mix(in srgb, var(--pipes-link) 8%, var(--pipes-surface));
            color: var(--pipes-text);
            padding: 0.75rem 1rem;
            overflow-wrap: anywhere;
        }
        .workspace-message.error {
            background: color-mix(in srgb, var(--pipes-danger) 8%, var(--pipes-surface));
            color: var(--pipes-danger);
        }
        .workspace-message[hidden] {
            display: none;
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
        .section-title:first-of-type {
            margin-top: 1rem;
        }
        .ability-search-header {
            background: var(--pipes-surface);
            margin: 0 -1rem;
            padding: 0 1rem 0.75rem;
            position: sticky;
            top: 0;
            z-index: 2;
        }
        .search {
            margin-bottom: 0.75rem;
        }
        .quick-steps {
            display: grid;
            gap: 0.45rem;
            margin-bottom: 0.95rem;
        }
        .quick-step-grid {
            display: grid;
            gap: 0.45rem;
            grid-template-columns: repeat(auto-fit, minmax(5.5rem, 1fr));
        }
        .quick-step {
            display: grid;
            gap: 0.18rem;
            min-width: 0;
            padding: 0.65rem;
            text-align: left;
        }
        .quick-step strong {
            font-size: 0.9rem;
        }
        .quick-step .meta {
            line-height: 1.25;
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
        .list-item[draggable="true"],
        .quick-step[draggable="true"] {
            cursor: grab;
        }
        .list-item.dragging,
        .quick-step.dragging {
            opacity: 0.55;
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
            overscroll-behavior: auto;
            min-height: 28rem;
            padding: 1.25rem;
            background-image:
                linear-gradient(var(--pipes-border) 1px, transparent 1px),
                linear-gradient(90deg, var(--pipes-border) 1px, transparent 1px);
            background-size: 36px 36px;
            background-color: color-mix(in srgb, var(--pipes-bg) 88%, var(--pipes-surface));
        }
        .canvas.drag-target {
            outline: 2px solid var(--pipes-accent);
            outline-offset: -2px;
        }
        .canvas-visibility {
            align-items: center;
            background: color-mix(in srgb, var(--pipes-text) 88%, transparent);
            border-color: transparent;
            box-shadow: 0 8px 20px color-mix(in srgb, #000 18%, transparent);
            color: var(--pipes-surface);
            display: inline-flex;
            gap: 0.35rem;
            left: 100%;
            margin-bottom: -2.35rem;
            padding: 0.42rem 0.6rem;
            position: sticky;
            top: 0.75rem;
            transform: translateX(calc(-100% - 0.75rem));
            width: max-content;
            z-index: 12;
        }
        .canvas-visibility[hidden] {
            display: none;
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
        .builder-list + .inspector,
        .app:has(.builder-list) .inspector {
            display: none;
        }
        .app:has(.builder-list) {
            grid-template-columns: 18rem minmax(24rem, 1fr);
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
        .flow-step-title-row {
            align-items: center;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 0.5rem;
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
            gap: 0.25rem;
            justify-content: flex-end;
        }
        .flow-step-actions button {
            align-items: center;
            display: inline-flex;
            height: 1.8rem;
            justify-content: center;
            line-height: 1;
            padding: 0;
            width: 1.8rem;
        }
        .list-args {
            border-top: 1px solid var(--pipes-border);
            display: grid;
            gap: 0.55rem;
            margin-top: 0.75rem;
            padding-top: 0.75rem;
        }
        .list-arg {
            border: 1px solid var(--pipes-border);
            border-radius: var(--pipes-radius);
            background: color-mix(in srgb, var(--pipes-surface) 76%, var(--pipes-surface-alt));
            padding: 0.5rem 0.6rem;
        }
        .list-arg[open] {
            display: grid;
            gap: 0.45rem;
        }
        .list-arg summary {
            color: var(--pipes-muted);
            cursor: pointer;
            font-size: 0.78rem;
            font-weight: 600;
            list-style-position: inside;
        }
        .input-mode {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        .input-mode label {
            align-items: center;
            color: var(--pipes-text);
            display: inline-flex;
            font-size: 0.84rem;
            font-weight: 500;
            gap: 0.35rem;
        }
        .input-mode input {
            flex: 0 0 auto;
        }
        .list-arg textarea {
            min-height: 4rem;
        }
        .checkbox-grid {
            display: grid;
            gap: 0.4rem;
            grid-template-columns: repeat(auto-fit, minmax(9rem, 1fr));
        }
        .checkbox-grid label {
            align-items: center;
            background: var(--pipes-surface-alt);
            border: 1px solid var(--pipes-border);
            border-radius: var(--pipes-radius);
            color: var(--pipes-text);
            display: flex;
            font-size: 0.82rem;
            font-weight: 500;
            gap: 0.4rem;
            min-width: 0;
            padding: 0.4rem 0.5rem;
        }
        .checkbox-grid input {
            flex: 0 0 auto;
        }
        .checkbox-grid span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .output-preview {
            border-top: 1px solid var(--pipes-border);
            display: grid;
            gap: 0.45rem;
            margin-top: 0.75rem;
            padding-top: 0.75rem;
        }
        .output-preview-header {
            align-items: center;
            display: flex;
            gap: 0.5rem;
            justify-content: space-between;
        }
        .output-preview-title {
            font-size: 0.86rem;
        }
        .output-preview-empty {
            color: var(--pipes-muted);
            font-size: 0.84rem;
        }
        .output-preview-empty.error {
            color: var(--pipes-danger);
            overflow-wrap: anywhere;
        }
        .output-preview pre {
            background: var(--pipes-surface-alt);
            border: 1px solid var(--pipes-border);
            border-radius: var(--pipes-radius);
            margin: 0;
            max-height: 14rem;
            overflow: auto;
            padding: 0.65rem;
            white-space: pre-wrap;
        }
        .output-preview-actions {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .output-preview-actions button {
            padding: 0.35rem 0.5rem;
        }
        .rendered-output {
            background: var(--pipes-surface);
            border: 1px solid var(--pipes-border);
            border-radius: var(--pipes-radius);
            overflow: auto;
            padding: 0.65rem;
        }
        .rendered-output p {
            margin: 0;
        }
        .rendered-output ul {
            margin: 0;
            padding-left: 1.2rem;
        }
        .rendered-output table {
            border-collapse: collapse;
            width: 100%;
        }
        .rendered-output th,
        .rendered-output td {
            border-bottom: 1px solid var(--pipes-border);
            padding: 0.35rem 0.4rem;
            text-align: left;
            vertical-align: top;
        }
        .rendered-output tbody tr:last-child th,
        .rendered-output tbody tr:last-child td {
            border-bottom: 0;
        }
        .rendered-output th {
            color: var(--pipes-muted);
            font-weight: 600;
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
            padding-right: 2rem;
        }
        .node-delete {
            align-items: center;
            display: inline-flex;
            height: 1.75rem;
            justify-content: center;
            line-height: 1;
            padding: 0;
            position: absolute;
            right: 0.55rem;
            top: 0.55rem;
            width: 1.75rem;
        }
        .node-debug-preview {
            border: 1px solid var(--pipes-border);
            border-radius: var(--pipes-radius);
            background: var(--pipes-surface-alt);
            color: var(--pipes-text);
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace;
            font-size: 0.76rem;
            line-height: 1.35;
            margin-top: 0.6rem;
            max-height: 8rem;
            overflow: auto;
            padding: 0.55rem;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }
        .node-debug-preview.is-empty {
            color: var(--pipes-muted);
            font-family: inherit;
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
            touch-action: none;
        }
        .port-row {
            position: relative;
        }
        .port-row .input-port.bound {
            padding-right: 2rem;
        }
        .port-clear {
            align-items: center;
            display: inline-flex;
            height: 1.35rem;
            justify-content: center;
            line-height: 1;
            padding: 0;
            position: absolute;
            right: 0.18rem;
            top: 50%;
            transform: translateY(-50%);
            width: 1.35rem;
            z-index: 6;
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
        .input-port.has-value {
            display: grid;
            gap: 0.16rem;
        }
        .port-value {
            color: var(--pipes-muted);
            display: block;
            font-family: ui-monospace, SFMono-Regular, Consolas, "Liberation Mono", monospace;
            font-size: 0.66rem;
            line-height: 1.25;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
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
        .input-popover {
            position: absolute;
            z-index: 8;
            width: min(22rem, calc(100vw - 2rem));
            border: 1px solid var(--pipes-border);
            border-radius: var(--pipes-radius);
            background: var(--pipes-surface);
            box-shadow: 0 16px 38px color-mix(in srgb, #000 16%, transparent);
            padding: 0.75rem;
        }
        .input-popover .list-args {
            border-top: 0;
            margin-top: 0;
            padding-top: 0;
        }
        .input-popover-header {
            align-items: center;
            display: flex;
            gap: 0.5rem;
            justify-content: space-between;
            margin-bottom: 0.6rem;
        }
        .input-popover-title {
            font-size: 0.84rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .input-popover-close {
            flex: 0 0 auto;
            padding: 0.25rem 0.45rem;
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
        .workspace:not(.builder-list) .output {
            max-height: 15rem;
            overflow: auto;
            box-shadow: 0 -6px 18px color-mix(in srgb, #000 7%, transparent);
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
        .workspace:not(.builder-list) .output pre {
            max-height: 10rem;
        }
        .run-error {
            border: 1px solid color-mix(in srgb, var(--pipes-danger) 38%, var(--pipes-border));
            border-radius: var(--pipes-radius);
            background: color-mix(in srgb, var(--pipes-danger) 8%, var(--pipes-surface));
            color: var(--pipes-danger);
            margin-top: 0.65rem;
            padding: 0.65rem 0.75rem;
            overflow-wrap: anywhere;
        }
        .run-error[hidden] {
            display: none;
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
                display: none;
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
            .ability-search-header {
                margin: 0 -0.75rem;
                padding: 0 0.75rem 0.75rem;
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
                justify-content: flex-start;
            }
            .flow-step-actions button {
                min-width: 0;
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

    <div id="pipes-app" class="app inspector-collapsed">
        <aside class="sidebar">
            <h1 class="brand">Pipes</h1>
            <button class="primary" data-action="new-pipe">New Pipe</button>

            <div class="ability-tray">
                <div class="ability-search-header">
                    <h2 class="section-title">Abilities</h2>
                    <input class="search" type="search" data-ability-search placeholder="Search abilities">
                    <div class="quick-steps" data-quick-steps hidden></div>
                </div>
                <div class="list" data-abilities-list>
                    <div class="notice">Loading abilities...</div>
                </div>
            </div>

            <h2 class="section-title">Saved</h2>
            <div class="list" data-pipes-list>
                <div class="notice">Loading pipes...</div>
            </div>

            <h2 class="section-title">Starters</h2>
            <div class="list" data-examples-list>
                <div class="notice">Loading starters...</div>
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
                    <button data-action="undo" disabled>Undo</button>
                    <button data-action="toggle-inspector" aria-pressed="false">Show Inspector</button>
                    <button class="danger" data-action="delete">Delete</button>
                </div>
            </div>

            <div class="workspace-message" data-workspace-message hidden></div>

            <section class="list-builder" data-list-builder></section>

            <section class="canvas">
                <button type="button" class="canvas-visibility" data-out-of-view-indicator hidden></button>
                <div class="graph" data-graph>
                    <svg class="edges" data-edges></svg>
                    <div class="empty" data-empty>Add abilities from the ability tray to build a flow.</div>
                </div>
            </section>

            <section class="output" data-debug-output>
                <strong>Debug Output</strong>
                <div class="run-error" data-run-error hidden></div>
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
            nonce: <?php echo wp_json_encode( wp_create_nonce( 'wp_rest' ) ); ?>,
            build: <?php echo wp_json_encode( (string) filemtime( __FILE__ ) ); ?>
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
            runOutput: '{}',
            runError: '',
            activeBindingTarget: '',
            builderMode: window.matchMedia('(max-width: 760px)').matches ? 'list' : 'visual',
            listAddOpen: false,
            listAddIndex: null,
            listAddSearch: '',
            openListArg: '',
            visualInputPopover: null,
            connectionDraft: null,
            inspectorOpen: false,
            undoStack: [],
            undoBaseline: null,
            dirty: false
        };

        const draftKey = 'pipes.builderDraft.v1';
        const maxUndoSteps = 50;

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
                const error = new Error(data.message || data?.data?.message || 'Request failed');
                error.data = data.data || {};
                error.response = data;
                throw error;
            }
            return data;
        };

        const setStatus = (message, isError = false) => {
            const status = $('[data-status]');
            status.textContent = message;
            status.classList.toggle('error', isError);
        };

        const setWorkspaceMessage = (message = '', isError = false) => {
            const element = $('[data-workspace-message]');
            if (!element) {
                return;
            }
            element.textContent = message;
            element.classList.toggle('error', isError);
            element.hidden = !message;
        };

        const reportError = (message) => {
            setWorkspaceMessage(message, true);
            setStatus('Needs attention', true);
        };

        const setRunError = (message = '') => {
            state.runError = message;
            const runError = $('[data-run-error]');
            if (!runError) {
                return;
            }
            runError.textContent = message;
            runError.hidden = !message;
        };

        const setRunOutput = (value) => {
            state.runOutput = value;
            const output = $('[data-output]');
            if (output) {
                output.textContent = value;
            }
        };

        const renderRunOutput = () => {
            setRunError(state.runError);
            setRunOutput(state.runOutput);
        };

        const cloneJson = (value) => JSON.parse(JSON.stringify(value));

        const editableSnapshot = () => ({
            selectedPipeId: state.selectedPipeId,
            selectedNodeId: state.selectedNodeId,
            title: state.title,
            graph: cloneJson(state.graph),
            activeBindingTarget: state.activeBindingTarget,
            builderMode: state.builderMode
        });

        const sameSnapshot = (left, right) => JSON.stringify(left) === JSON.stringify(right);

        const renderUndoState = () => {
            const undo = $('[data-action="undo"]');
            if (undo) {
                undo.disabled = !state.undoStack.length;
            }
        };

        const resetUndoHistory = () => {
            state.undoStack = [];
            state.undoBaseline = editableSnapshot();
            renderUndoState();
        };

        const captureUndoStep = () => {
            const current = editableSnapshot();
            if (!state.undoBaseline) {
                state.undoBaseline = current;
                renderUndoState();
                return;
            }
            if (sameSnapshot(state.undoBaseline, current)) {
                renderUndoState();
                return;
            }
            state.undoStack.push(state.undoBaseline);
            if (state.undoStack.length > maxUndoSteps) {
                state.undoStack.shift();
            }
            state.undoBaseline = current;
            renderUndoState();
        };

        const applySnapshot = (snapshot) => {
            state.selectedPipeId = snapshot.selectedPipeId || null;
            state.selectedNodeId = snapshot.selectedNodeId || snapshot.graph?.nodes?.[0]?.id || null;
            state.title = snapshot.title || 'Untitled Pipe';
            state.graph = snapshot.graph || { nodes: [], edges: [] };
            normalizeGraphArgs();
            state.lastRunResults = {};
            state.activeBindingTarget = snapshot.activeBindingTarget || '';
            state.builderMode = snapshot.builderMode === 'list' ? 'list' : 'visual';
            state.listAddOpen = false;
            state.listAddIndex = null;
            state.listAddSearch = '';
            state.visualInputPopover = null;
            state.connectionDraft = null;
            $('[data-title]').value = state.title;
            clearRunResults();
            setPipeUrl(state.selectedPipeId);
        };

        const undoLastChange = () => {
            const snapshot = state.undoStack.pop();
            if (!snapshot) {
                renderUndoState();
                return;
            }
            applySnapshot(snapshot);
            state.dirty = true;
            state.undoBaseline = editableSnapshot();
            saveLocalDraft();
            setWorkspaceMessage();
            setStatus('Unsaved changes');
            render();
        };

        const saveLocalDraft = () => {
            try {
                window.localStorage.setItem(draftKey, JSON.stringify({
                    selectedPipeId: state.selectedPipeId,
                    selectedNodeId: state.selectedNodeId,
                    title: state.title,
                    graph: state.graph,
                    activeBindingTarget: state.activeBindingTarget,
                    builderMode: state.builderMode,
                    savedAt: new Date().toISOString()
                }));
            } catch (error) {
                // Local drafts are best-effort; saving the pipe remains the durable path.
            }
        };

        const clearLocalDraft = () => {
            try {
                window.localStorage.removeItem(draftKey);
            } catch (error) {
                // Ignore storage errors.
            }
        };

        const readLocalDraft = () => {
            try {
                return JSON.parse(window.localStorage.getItem(draftKey) || 'null');
            } catch (error) {
                return null;
            }
        };

        const restoreLocalDraft = (draft) => {
            if (!draft || !draft.graph || !Array.isArray(draft.graph.nodes)) {
                return false;
            }
            state.selectedPipeId = draft.selectedPipeId || null;
            state.selectedNodeId = draft.selectedNodeId || draft.graph.nodes[0]?.id || null;
            state.title = draft.title || 'Untitled Pipe';
            state.graph = draft.graph || { nodes: [], edges: [] };
            normalizeGraphArgs();
            state.lastRunResults = {};
            state.activeBindingTarget = draft.activeBindingTarget || '';
            state.builderMode = draft.builderMode === 'list' ? 'list' : 'visual';
            state.listAddOpen = false;
            state.listAddIndex = null;
            state.listAddSearch = '';
            state.visualInputPopover = null;
            state.connectionDraft = null;
            state.dirty = true;
            $('[data-title]').value = state.title;
            clearRunResults();
            setPipeUrl(state.selectedPipeId);
            render();
            resetUndoHistory();
            setStatus(`Restored unsaved draft${draft.savedAt ? ` from ${new Date(draft.savedAt).toLocaleString()}` : ''}`);
            return true;
        };

        const markDirty = () => {
            state.dirty = true;
            captureUndoStep();
            setStatus('Unsaved changes');
            saveLocalDraft();
        };

        const clearRunResults = () => {
            state.lastRunResults = {};
            setRunError();
            setRunOutput('{}');
        };

        const normalizeArgs = (args) => {
            if (args && typeof args === 'object' && !Array.isArray(args)) {
                return args;
            }
            if (Array.isArray(args)) {
                return Object.fromEntries(Object.entries(args).filter(([key]) => Number.isNaN(Number(key))));
            }
            return {};
        };

        const ensureNodeArgs = (node) => {
            node.args = normalizeArgs(node.args);
            return node.args;
        };

        const normalizeGraphArgs = () => {
            const nodeIds = new Set((state.graph.nodes || []).map((node) => node.id));
            for (const node of state.graph.nodes || []) {
                ensureNodeArgs(node);
                node.bindings = (node.bindings || []).filter((binding) => (
                    binding.source &&
                    binding.source !== node.id &&
                    nodeIds.has(binding.source)
                ));
            }
            syncEdgesFromBindings();
        };

        const savedArgsStatus = () => {
            const node = selectedNode();
            if (!node) {
                return 'Saved';
            }
            const args = JSON.stringify(ensureNodeArgs(node));
            return `Saved ${node.label || node.ability_id} args: ${args.length > 180 ? `${args.slice(0, 177)}...` : args}`;
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
            $('.app').classList.toggle('inspector-collapsed', !state.inspectorOpen);
            const inspectorToggle = $('[data-action="toggle-inspector"]');
            if (inspectorToggle) {
                inspectorToggle.textContent = 'Show Inspector';
                inspectorToggle.setAttribute('aria-pressed', state.inspectorOpen ? 'true' : 'false');
                inspectorToggle.hidden = state.inspectorOpen;
            }
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
                default: details?.default,
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
                props.slice(0, 6).map((prop) => ({
                    name: prop.name,
                    label: inputPortLabel(prop),
                    title: prop.description || prop.name
                })) :
                Object.keys(ensureNodeArgs(node)).slice(0, 6).map((key) => ({ name: key, label: key }));
            for (const binding of node.bindings || []) {
                if (binding.target && !ports.some((port) => port.name === binding.target)) {
                    ports.push({ name: binding.target, label: binding.target });
                }
            }
            return ports;
        };

        const inputPortLabel = (prop) => (
            typeList(prop?.type).includes('array') ? `${prop.name}[]` : prop.name
        );

        const outputPortsForNode = (node) => {
            const runResult = state.lastRunResults[node.id]?.result;
            let ports = [];
            if (runResult !== undefined) {
                ports = flattenPaths(runResult)
                    .filter((item) => item.path)
                    .slice(0, 6)
                    .map((item) => ({
                        path: item.path,
                        label: outputPortLabel(item.path, valueType(item.value)),
                        value: formatPathValue(item.value),
                        type: valueType(item.value),
                        title: pathDescription(item.path, item.value)
                    }));
            } else {
                const ability = abilityById(node.ability_id);
                const props = schemaProperties(ability?.output_schema);
                ports = props.length ?
                    props.slice(0, 6).map((prop) => ({ path: prop.name, label: outputPortLabel(prop.name, prop.type), value: prop.type, type: prop.type })) :
                    [{ path: '', label: 'result', value: '', type: 'any' }];
            }
            for (const targetNode of state.graph.nodes) {
                for (const binding of targetNode.bindings || []) {
                    if (binding.source === node.id && !ports.some((port) => port.path === (binding.path || ''))) {
                        ports.push({ path: binding.path || '', label: pathLabel(binding.path || '') || 'result', value: 'bound', type: 'any' });
                    }
                }
            }
            return ports;
        };

        const outputPortLabel = (path, type = 'any') => {
            const label = pathLabel(path);
            return typeList(type).includes('array') && !label.endsWith('[]') ? `${label}[]` : label;
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
                if (prop.default !== undefined) {
                    args[prop.name] = prop.default;
                } else if (prop.required) {
                    args[prop.name] = '';
                }
            }
            return args;
        };

        const flattenPaths = (value, prefix = '', paths = []) => {
            if (paths.length >= 40) {
                return paths;
            }
            const isArrayItemPath = /^\d+$/.test(String(prefix).split('.').pop() || '');
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
            if (prefix && !isArrayItemPath) {
                paths.push({ path: prefix, value });
            }
            Object.entries(value).slice(0, 16).forEach(([key, item]) => {
                flattenPaths(item, prefix ? `${prefix}.${key}` : key, paths);
            });
            return paths;
        };

        const pathLabel = (path) => {
            const parts = String(path || '').split('.').filter(Boolean);
            if (!parts.length) {
                return 'result';
            }
            return parts.reduce((label, part) => {
                if (/^\d+$/.test(part)) {
                    return `${label || 'item'}[]`;
                }
                return label ? `${label}.${part}` : part;
            }, '');
        };

        const pathDescription = (path, value) => {
            const label = pathLabel(path);
            const indexedParts = String(path || '').split('.').filter((part) => /^\d+$/.test(part));
            const valuePreview = formatPathValue(value);
            if (!indexedParts.length) {
                return valuePreview ? `${label}: ${valuePreview}` : label;
            }
            const firstIndex = indexedParts[0];
            const description = firstIndex === '0' ? 'first array item' : `array item ${Number(firstIndex) + 1}`;
            return valuePreview ? `${label} (${description}): ${valuePreview}` : `${label} (${description})`;
        };

        const pathValue = (value, path) => {
            const parts = String(path || '').trim().split('.').filter(Boolean);
            let cursor = value;
            for (const part of parts) {
                if (cursor === null || cursor === undefined) {
                    return undefined;
                }
                if (Array.isArray(cursor)) {
                    const index = Number.parseInt(part, 10);
                    cursor = Number.isNaN(index) ? undefined : cursor[index];
                } else if (typeof cursor === 'object') {
                    cursor = cursor[part];
                } else {
                    return undefined;
                }
            }
            return cursor;
        };

        const itemPathSuggestionsFor = (node) => {
            const itemsBinding = (node.bindings || []).find((binding) => binding.target === 'items');
            const sourceResult = itemsBinding ? state.lastRunResults[itemsBinding.source]?.result : undefined;
            const items = sourceResult === undefined ? undefined : pathValue(sourceResult, itemsBinding.path || '');
            if (!Array.isArray(items)) {
                return [];
            }
            const seen = new Set();
            const suggestions = [];
            const collectItemPaths = (value, prefix = '') => {
                if (suggestions.length >= 120 || value === null || value === undefined) {
                    return;
                }
                if (Array.isArray(value)) {
                    if (prefix && !seen.has(prefix)) {
                        seen.add(prefix);
                        suggestions.push(prefix);
                    }
                    if (value.length) {
                        collectItemPaths(value[0], prefix ? `${prefix}.0` : '');
                    }
                    return;
                }
                if (typeof value !== 'object') {
                    if (prefix && !seen.has(prefix)) {
                        seen.add(prefix);
                        suggestions.push(prefix);
                    }
                    return;
                }
                for (const [key, item] of Object.entries(value)) {
                    const path = prefix ? `${prefix}.${key}` : key;
                    if (!seen.has(path)) {
                        seen.add(path);
                        suggestions.push(path);
                    }
                    if (item && typeof item === 'object') {
                        collectItemPaths(item, path);
                    }
                }
            };
            for (const item of items.slice(0, 5)) {
                collectItemPaths(item);
            }
            return suggestions.slice(0, 80);
        };

        const valueForBinding = (node, target) => {
            const binding = (node.bindings || []).find((candidate) => candidate.target === target);
            const sourceResult = binding ? state.lastRunResults[binding.source]?.result : undefined;
            return sourceResult === undefined ? undefined : pathValue(sourceResult, binding.path || '');
        };

        const dashboardListColumnSuggestionsFor = (node) => {
            const value = valueForBinding(node, 'value') ?? state.lastRunResults[node.id]?.input?.value;
            if (!Array.isArray(value)) {
                return [];
            }
            const seen = new Set();
            const columns = [];
            for (const item of value.slice(0, 10)) {
                if (!item || typeof item !== 'object' || Array.isArray(item)) {
                    continue;
                }
                for (const key of Object.keys(item)) {
                    if (seen.has(key)) {
                        continue;
                    }
                    seen.add(key);
                    columns.push(key);
                }
            }
            return columns.slice(0, 80);
        };

        const dateFormatChoices = [
            ['F j, Y', 'January 12, 2026'],
            ['M j, Y', 'Jan 12, 2026'],
            ['l, F j, Y', 'Monday, January 12, 2026'],
            ['D, M j', 'Mon, Jan 12'],
            ['n/j/Y', '1/12/2026'],
            ['m/d/Y', '01/12/2026'],
            ['d.m.Y', '12.01.2026'],
            ['Y-m-d', '2026-01-12']
        ];

        const dateFormatterSampleValue = (node) => {
            const items = valueForBinding(node, 'items');
            const path = String(ensureNodeArgs(node).path || '');
            if (!Array.isArray(items) || !path) {
                return '2026-01-12';
            }
            for (const item of items.slice(0, 10)) {
                const value = pathValue(item, path);
                if (value !== null && value !== undefined && typeof value !== 'object') {
                    return String(value);
                }
            }
            return '2026-01-12';
        };

        const phpDatePreview = (format, sourceValue) => {
            const source = String(sourceValue || '2026-01-12');
            const date = new Date(/^\d{4}-\d{2}-\d{2}$/.test(source) ? `${source}T00:00:00` : source);
            if (Number.isNaN(date.getTime())) {
                return '';
            }
            const months = [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ];
            const shortMonths = months.map((month) => month.slice(0, 3));
            const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            const shortDays = days.map((day) => day.slice(0, 3));
            const pad = (value) => String(value).padStart(2, '0');
            const replacements = {
                Y: String(date.getFullYear()),
                y: String(date.getFullYear()).slice(-2),
                F: months[date.getMonth()],
                M: shortMonths[date.getMonth()],
                m: pad(date.getMonth() + 1),
                n: String(date.getMonth() + 1),
                d: pad(date.getDate()),
                j: String(date.getDate()),
                l: days[date.getDay()],
                D: shortDays[date.getDay()]
            };
            return String(format || 'F j, Y').replace(/[YyFMmndjlD]/g, (token) => replacements[token] ?? token);
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

        const isOutputNode = (node) => String(node.ability_id || '').startsWith('pipes/output-');
        const isDebugOutputNode = (node) => String(node.ability_id || '') === 'pipes/output-debug';
        const isDashboardOutputNode = (node) => [
            'pipes/output-dashboard-text',
            'pipes/output-dashboard-list'
        ].includes(String(node.ability_id || ''));

        const stringifyGlueValue = (value) => {
            if (value === null || value === undefined) {
                return '';
            }
            if (typeof value === 'object') {
                return JSON.stringify(value);
            }
            return String(value);
        };

        const appendTextElement = (parent, tagName, text) => {
            const element = document.createElement(tagName);
            element.textContent = text;
            parent.append(element);
            return element;
        };

        const isListArray = (value) => Array.isArray(value) && Object.keys(value).every((key) => String(Number(key)) === key);

        const renderValueHtml = (value, container, selectedColumns = []) => {
            container.innerHTML = '';
            if (value === null || value === undefined) {
                const paragraph = document.createElement('p');
                appendTextElement(paragraph, 'em', 'No output.');
                container.append(paragraph);
                return;
            }
            if (typeof value !== 'object') {
                appendTextElement(container, 'p', String(value));
                return;
            }
            if (isListArray(value)) {
                if (!value.length) {
                    const paragraph = document.createElement('p');
                    appendTextElement(paragraph, 'em', 'No items.');
                    container.append(paragraph);
                    return;
                }
                const first = value[0];
                if (first && typeof first === 'object' && !Array.isArray(first)) {
                    const availableColumns = Object.keys(first);
                    let columns = selectedColumns.length ?
                        selectedColumns.filter((column) => availableColumns.includes(column)) :
                        availableColumns.slice(0, 6);
                    if (!columns.length) {
                        columns = availableColumns.slice(0, 6);
                    }
                    const table = document.createElement('table');
                    const thead = document.createElement('thead');
                    const headRow = document.createElement('tr');
                    for (const column of columns) {
                        appendTextElement(headRow, 'th', column);
                    }
                    thead.append(headRow);
                    table.append(thead);
                    const tbody = document.createElement('tbody');
                    for (const row of value.slice(0, 10)) {
                        const tableRow = document.createElement('tr');
                        for (const column of columns) {
                            appendTextElement(tableRow, 'td', stringifyGlueValue(row?.[column]));
                        }
                        tbody.append(tableRow);
                    }
                    table.append(tbody);
                    container.append(table);
                    return;
                }
                const list = document.createElement('ul');
                for (const item of value.slice(0, 10)) {
                    appendTextElement(list, 'li', stringifyGlueValue(item));
                }
                container.append(list);
                return;
            }
            const table = document.createElement('table');
            const tbody = document.createElement('tbody');
            for (const [key, item] of Object.entries(value).slice(0, 12)) {
                const row = document.createElement('tr');
                appendTextElement(row, 'th', key);
                appendTextElement(row, 'td', stringifyGlueValue(item));
                tbody.append(row);
            }
            table.append(tbody);
            container.append(table);
        };

        const compactPreview = (value) => {
            if (value === null) {
                return 'null';
            }
            if (value === undefined) {
                return 'No value';
            }
            if (Array.isArray(value)) {
                return JSON.stringify(value.slice(0, 5), null, 2);
            }
            if (typeof value === 'object') {
                return JSON.stringify(value, null, 2);
            }
            return String(value);
        };

        const inputValuePreview = (node, portName) => {
            const value = ensureNodeArgs(node)[portName];
            if (value === undefined) {
                return '';
            }
            if (value && typeof value === 'object' && value.__pipes_user_query) {
                return 'Ask user';
            }
            const preview = compactPreview(value).replace(/\s+/g, ' ').trim();
            if (!preview) {
                return '""';
            }
            return preview.length > 42 ? `${preview.slice(0, 39)}...` : preview;
        };

        const debugPreviewValue = (node) => {
            const run = state.lastRunResults[node.id];
            if (!run) {
                return undefined;
            }
            if (run.input && Object.prototype.hasOwnProperty.call(run.input, 'value')) {
                return run.input.value;
            }
            const result = run.result;
            if (result && typeof result === 'object' && Object.prototype.hasOwnProperty.call(result, 'value')) {
                return result.value;
            }
            return run.result;
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

        const bindPathToInput = (targetNode, targetName, sourceId, path) => {
            if (!targetNode || !targetName) {
                return false;
            }
            if (targetNode.id === sourceId) {
                return false;
            }
            const source = nodeById(sourceId);
            const inputProp = inputPropForNode(targetNode, targetName);
            const outputPort = source ? outputPortsForNode(source).find((port) => port.path === (path || '')) : null;
            if (inputProp && outputPort && !typesCompatible(inputProp.type, outputPort.type || 'any')) {
                setWorkspaceMessage(`Cannot bind ${outputPort.label || 'output'} to ${targetName}; the types do not match.`, true);
                return false;
            }
            targetNode.bindings = targetNode.bindings || [];
            const existing = targetNode.bindings.find((binding) => binding.target === targetName);
            if (existing) {
                existing.source = sourceId;
                existing.path = path;
            } else {
                targetNode.bindings.push({ target: targetName, source: sourceId, path });
            }
            delete ensureNodeArgs(targetNode)[targetName];
            syncEdgesFromBindings();
            markDirty();
            setWorkspaceMessage();
            render();
            return true;
        };

        const removeBindingTarget = (node, target) => {
            node.bindings = (node.bindings || []).filter((candidate) => candidate.target !== target);
            if (state.activeBindingTarget === target) {
                state.visualInputPopover = null;
            }
            syncEdgesFromBindings();
            markDirty();
        };

        const graphPointerPoint = (event) => {
            const graphRect = $('[data-graph]').getBoundingClientRect();
            return {
                x: event.clientX - graphRect.left,
                y: event.clientY - graphRect.top
            };
        };

        const nodeSize = () => (
            window.matchMedia('(max-width: 760px)').matches ?
                { width: 224, height: 180 } :
                { width: 288, height: 210 }
        );

        const canvasViewportInGraph = () => {
            const canvas = $('.canvas');
            const graph = $('[data-graph]');
            const canvasRect = canvas.getBoundingClientRect();
            const graphRect = graph.getBoundingClientRect();
            return {
                left: canvasRect.left - graphRect.left,
                top: canvasRect.top - graphRect.top,
                right: canvasRect.right - graphRect.left,
                bottom: canvasRect.bottom - graphRect.top
            };
        };

        const clampNodePositionToViewport = (position) => {
            const viewport = canvasViewportInGraph();
            const size = nodeSize();
            const gutter = 16;
            const minX = Math.max(gutter, viewport.left + gutter);
            const minY = Math.max(gutter, viewport.top + gutter);
            const maxX = Math.max(minX, viewport.right - size.width - gutter);
            const maxY = Math.max(minY, viewport.bottom - size.height - gutter);
            return {
                x: Math.round(Math.min(Math.max(position.x, minX), maxX)),
                y: Math.round(Math.min(Math.max(position.y, minY), maxY))
            };
        };

        const defaultNodePosition = (index) => {
            const mobile = window.matchMedia('(max-width: 760px)').matches;
            const position = mobile ?
                { x: 36, y: 42 + index * 245 } :
                { x: 28 + index * 320, y: 42 + (index % 3) * 48 };
            return clampNodePositionToViewport(position);
        };

        const droppedNodePosition = (event) => {
            const point = graphPointerPoint(event);
            const size = nodeSize();
            return clampNodePositionToViewport({
                x: point.x - size.width / 2,
                y: point.y - 28
            });
        };

        const attachAbilityDrag = (element, ability) => {
            element.addEventListener('dragstart', (event) => {
                event.dataTransfer.effectAllowed = 'copy';
                event.dataTransfer.setData('text/pipes-ability-id', ability.id);
                event.dataTransfer.setData('text/plain', ability.id);
                element.classList.add('dragging');
            });
            element.addEventListener('dragend', () => {
                element.classList.remove('dragging');
                $('.canvas')?.classList.remove('drag-target');
            });
        };

        const abilityIdFromDrag = (event) => (
            event.dataTransfer.getData('text/pipes-ability-id') ||
            event.dataTransfer.getData('text/plain')
        );

        const dragHasAbility = (event) => (
            Array.from(event.dataTransfer?.types || []).includes('text/pipes-ability-id')
        );

        const updateOutOfViewIndicator = () => {
            const indicator = $('[data-out-of-view-indicator]');
            const canvas = $('.canvas');
            if (!indicator || !canvas || state.builderMode === 'list') {
                if (indicator) {
                    indicator.hidden = true;
                }
                return;
            }
            const canvasRect = canvas.getBoundingClientRect();
            const hidden = [];
            const directions = new Set();
            $$('.node', $('[data-graph]')).forEach((nodeElement) => {
                const rect = nodeElement.getBoundingClientRect();
                const offLeft = rect.left < canvasRect.left;
                const offRight = rect.right > canvasRect.right;
                const offTop = rect.top < canvasRect.top;
                const offBottom = rect.bottom > canvasRect.bottom;
                if (!(offLeft || offRight || offTop || offBottom)) {
                    return;
                }
                hidden.push(nodeElement);
                if (offLeft) {
                    directions.add('left');
                }
                if (offRight) {
                    directions.add('right');
                }
                if (offTop) {
                    directions.add('above');
                }
                if (offBottom) {
                    directions.add('below');
                }
            });
            indicator.hidden = !hidden.length;
            if (!hidden.length) {
                indicator.removeAttribute('data-target-node-id');
                return;
            }
            const label = hidden.length === 1 ? '1 item out of view' : `${hidden.length} items out of view`;
            const directionText = Array.from(directions).join(', ');
            indicator.textContent = directionText ? `${label}: ${directionText}` : label;
            indicator.dataset.targetNodeId = hidden[0].dataset.nodeId || '';
        };

        const scrollToOutOfViewNode = () => {
            const nodeId = $('[data-out-of-view-indicator]')?.dataset.targetNodeId || '';
            const nodeElement = nodeId ? $(`.node[data-node-id="${selectorEscape(nodeId)}"]`) : null;
            if (nodeElement) {
                nodeElement.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'smooth' });
            }
        };

        const normalizeWheelDelta = (event) => {
            if (event.deltaMode === WheelEvent.DOM_DELTA_LINE) {
                return event.deltaY * 16;
            }
            if (event.deltaMode === WheelEvent.DOM_DELTA_PAGE) {
                return event.deltaY * window.innerHeight;
            }
            return event.deltaY;
        };

        const scrollPageFromCanvasWheel = (event) => {
            if (event.defaultPrevented || event.ctrlKey || state.builderMode === 'list') {
                return;
            }
            if (event.shiftKey || Math.abs(event.deltaX) > Math.abs(event.deltaY)) {
                return;
            }
            const scroller = document.scrollingElement || document.documentElement;
            const deltaY = normalizeWheelDelta(event);
            const maxScroll = scroller.scrollHeight - scroller.clientHeight;
            const nextScroll = Math.min(Math.max(scroller.scrollTop + deltaY, 0), maxScroll);
            if (nextScroll === scroller.scrollTop) {
                return;
            }
            event.preventDefault();
            scroller.scrollTop = nextScroll;
        };

        const startConnectionDraft = (sourceId, path, event, options = {}) => {
            state.connectionDraft = {
                sourceId,
                path: path || '',
                pointer: graphPointerPoint(event)
            };
            state.visualInputPopover = null;
            setWorkspaceMessage('Choose an input port to connect this output.');
            if (options.preserveGraph) {
                $('.input-popover')?.remove();
                renderEdges();
            } else {
                renderGraph();
            }
        };

        const updateConnectionDraft = (event) => {
            if (!state.connectionDraft) {
                return;
            }
            state.connectionDraft.pointer = graphPointerPoint(event);
            renderEdges();
        };

        const cancelConnectionDraft = () => {
            if (!state.connectionDraft) {
                return;
            }
            state.connectionDraft = null;
            setWorkspaceMessage();
            renderGraph();
        };

        const completeConnectionDraft = (targetNode, targetName) => {
            const draft = state.connectionDraft;
            if (!draft) {
                return false;
            }
            state.connectionDraft = null;
            state.selectedNodeId = targetNode.id;
            state.activeBindingTarget = targetName;
            state.openListArg = `${targetNode.id}.${targetName}`;
            if (!bindPathToInput(targetNode, targetName, draft.sourceId, draft.path)) {
                renderGraph();
            }
            return true;
        };

        const inputPortAtPoint = (clientX, clientY) => {
            const target = document.elementFromPoint(clientX, clientY)?.closest('.port[data-port-kind="input"]');
            if (!target) {
                return null;
            }
            const node = nodeById(target.dataset.nodeId);
            const portName = target.dataset.portName || '';
            if (!node || !portName) {
                return null;
            }
            return { node, portName };
        };

        const attachOutputPortDrag = (output, node, port) => {
            let drag = null;
            output.addEventListener('pointerdown', (event) => {
                if (event.button !== 0) {
                    return;
                }
                event.stopPropagation();
                drag = {
                    pointerId: event.pointerId,
                    startX: event.clientX,
                    startY: event.clientY,
                    moved: false
                };
                output.setPointerCapture(event.pointerId);
                startConnectionDraft(node.id, port.path, event, { preserveGraph: true });
            });
            output.addEventListener('pointermove', (event) => {
                if (!drag || drag.pointerId !== event.pointerId) {
                    return;
                }
                event.preventDefault();
                event.stopPropagation();
                const dx = event.clientX - drag.startX;
                const dy = event.clientY - drag.startY;
                if (Math.abs(dx) + Math.abs(dy) > 3) {
                    drag.moved = true;
                }
                updateConnectionDraft(event);
            });
            const finish = (event) => {
                if (!drag || drag.pointerId !== event.pointerId) {
                    return;
                }
                event.preventDefault();
                event.stopPropagation();
                const target = drag.moved ? inputPortAtPoint(event.clientX, event.clientY) : null;
                if (output.hasPointerCapture(event.pointerId)) {
                    output.releasePointerCapture(event.pointerId);
                }
                if (target) {
                    completeConnectionDraft(target.node, target.portName);
                }
                if (drag.moved) {
                    output.dataset.connectionDragged = 'true';
                    window.setTimeout(() => {
                        delete output.dataset.connectionDragged;
                    }, 0);
                }
                drag = null;
            };
            output.addEventListener('pointerup', finish);
            output.addEventListener('pointercancel', (event) => {
                if (!drag || drag.pointerId !== event.pointerId) {
                    return;
                }
                event.preventDefault();
                event.stopPropagation();
                if (output.hasPointerCapture(event.pointerId)) {
                    output.releasePointerCapture(event.pointerId);
                }
                cancelConnectionDraft();
                drag = null;
            });
        };

        const defaultBindingForNode = (node, props) => {
            const source = bindingSourceNodesFor(node)[0];
            return {
                target: props[0]?.name || '',
                source: source?.id || '',
                path: ''
            };
        };

        const inputPropForNode = (node, propName) => (
            schemaProperties(abilityById(node.ability_id)?.input_schema)
                .find((prop) => prop.name === propName)
        );

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

        const isUserQueryArg = (value) => (
            value && typeof value === 'object' && value.__pipes_user_query
        );

        const userQuestionKey = (node, propName) => `${node.id}.${propName}`;

        const collectUserAnswers = () => {
            const answers = {};
            for (const node of state.graph.nodes) {
                for (const [key, value] of Object.entries(ensureNodeArgs(node))) {
                    if (!isUserQueryArg(value)) {
                        continue;
                    }
                    const answer = window.prompt(value.question || key);
                    if (answer === null) {
                        throw new Error('Run cancelled');
                    }
                    answers[userQuestionKey(node, key)] = answer;
                }
            }
            return answers;
        };

        const flushNodeArgs = () => {
            $$('[data-node-arg-node][data-node-arg]').forEach((control) => {
                const node = nodeById(control.dataset.nodeArgNode);
                const prop = {
                    name: control.dataset.nodeArg,
                    type: control.dataset.nodeArgType || 'string'
                };
                if (!node) {
                    return;
                }
                ensureNodeArgs(node);
                try {
                    const nextValue = parseArgValue(prop, control.value, control.checked);
                    if (nextValue === undefined || Number.isNaN(nextValue)) {
                        delete node.args[prop.name];
                    } else {
                        node.args[prop.name] = nextValue;
                    }
                } catch (error) {
                    // Leave the existing value in place; the visible control remains editable.
                }
            });
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

        const addNode = (ability, insertIndex = state.graph.nodes.length, options = {}) => {
            const index = Math.max(0, Math.min(insertIndex, state.graph.nodes.length));
            const id = `node-${Date.now().toString(36)}-${index}`;
            const node = {
                id,
                ability_id: ability.id,
                label: ability.label,
                args: defaultArgsForAbility(ability),
                bindings: [],
                position: options.position ? clampNodePositionToViewport(options.position) : defaultNodePosition(index)
            };
            state.graph.nodes.splice(index, 0, node);
            syncEdgesFromBindings();
            clearRunResults();
            state.selectedNodeId = id;
            markDirty();
            render();
        };

        const newPipe = () => {
            clearLocalDraft();
            state.selectedPipeId = null;
            state.selectedNodeId = null;
            state.title = 'Untitled Pipe';
            state.graph = { nodes: [], edges: [] };
            state.lastRunResults = {};
            state.activeBindingTarget = '';
            state.listAddOpen = false;
            state.listAddIndex = null;
            state.listAddSearch = '';
            state.visualInputPopover = null;
            state.connectionDraft = null;
            state.dirty = false;
            $('[data-title]').value = state.title;
            clearRunResults();
            setWorkspaceMessage();
            setPipeUrl();
            setStatus('Not saved');
            render();
            resetUndoHistory();
            $('[data-ability-search]')?.focus();
        };

        const loadExample = async (example) => {
            setStatus('Loading starter...');
            setWorkspaceMessage();
            const data = await request(`examples/${example.id}`, { method: 'POST' });
            state.selectedPipeId = data.pipe.id;
            state.selectedNodeId = data.pipe.graph?.nodes?.[0]?.id || null;
            state.title = data.pipe.title || example.title || 'Untitled Pipe';
            state.graph = data.pipe.graph || { nodes: [], edges: [] };
            normalizeGraphArgs();
            state.lastRunResults = {};
            state.activeBindingTarget = '';
            state.listAddOpen = false;
            state.listAddIndex = null;
            state.listAddSearch = '';
            state.visualInputPopover = null;
            state.connectionDraft = null;
            state.dirty = false;
            clearLocalDraft();
            $('[data-title]').value = state.title;
            clearRunResults();
            setWorkspaceMessage();
            setPipeUrl(state.selectedPipeId);
            setStatus('Starter pipe loaded');
            await loadPipes();
            await loadExamples();
            render();
            resetUndoHistory();
        };

        const loadPipe = async (id, updateUrl = true) => {
            const data = await request(`pipes/${id}`);
            state.selectedPipeId = data.pipe.id;
            state.title = data.pipe.title;
            state.graph = data.pipe.graph || { nodes: [], edges: [] };
            normalizeGraphArgs();
            state.selectedNodeId = state.graph.nodes[0]?.id || null;
            state.lastRunResults = {};
            state.activeBindingTarget = '';
            state.listAddOpen = false;
            state.listAddIndex = null;
            state.listAddSearch = '';
            state.visualInputPopover = null;
            state.connectionDraft = null;
            state.dirty = false;
            clearLocalDraft();
            $('[data-title]').value = state.title;
            clearRunResults();
            setWorkspaceMessage();
            if (updateUrl) {
                setPipeUrl(state.selectedPipeId);
            }
            setStatus('Saved');
            render();
            resetUndoHistory();
        };

        const savePipe = async () => {
            flushNodeArgs();
            normalizeGraphArgs();
            state.title = $('[data-title]').value.trim() || 'Untitled Pipe';
            const path = state.selectedPipeId ? `pipes/${state.selectedPipeId}` : 'pipes';
            const data = await request(path, {
                method: 'POST',
                body: JSON.stringify({ title: state.title, graph: state.graph })
            });
            state.selectedPipeId = data.pipe.id;
            state.title = data.pipe.title;
            state.graph = data.pipe.graph;
            normalizeGraphArgs();
            state.dirty = false;
            clearLocalDraft();
            $('[data-title]').value = state.title;
            setPipeUrl(state.selectedPipeId);
            await loadPipes();
            render();
            resetUndoHistory();
            setWorkspaceMessage();
            setStatus(savedArgsStatus());
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
            setRunError();
            setWorkspaceMessage();
            flushNodeArgs();
            normalizeGraphArgs();
            const userAnswers = collectUserAnswers();
            try {
                const data = await request('run', {
                    method: 'POST',
                    body: JSON.stringify({
                        pipe_id: state.selectedPipeId || 0,
                        graph: state.graph,
                        confirm_destructive: $('[data-confirm-destructive]')?.checked || false,
                        user_answers: userAnswers
                    })
                });
                state.lastRunResults = data.results || {};
                setRunOutput(JSON.stringify(data, null, 2));
                setRunError();
                setWorkspaceMessage();
                setStatus(state.dirty ? 'Unsaved changes' : 'Run complete');
                render();
            } catch (error) {
                const errorOutput = error.response || { message: error.message, data: error.data };
                const outputText = [
                    error.message,
                    '',
                    JSON.stringify(errorOutput, null, 2)
                ].join('\n');
                setRunOutput(outputText);
                setRunError(error.message);
                if (error.data?.results) {
                    state.lastRunResults = error.data.results;
                    render();
                }
                setWorkspaceMessage(error.message, true);
                setStatus('Run failed', true);
            }
        };

        const loadPipes = async () => {
            const data = await request('pipes');
            state.pipes = data.pipes || [];
            renderPipes();
        };

        const loadAbilities = async () => {
            const data = await request('abilities');
            state.abilities = data.abilities || [];
            renderQuickSteps();
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
                button.addEventListener('click', () => loadPipe(pipe.id).catch((error) => reportError(error.message)));
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
                button.draggable = true;
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
                attachAbilityDrag(button, ability);
                button.addEventListener('click', () => addNode(ability));
                list.append(button);
            }
        };

        const renderQuickSteps = () => {
            const container = $('[data-quick-steps]');
            if (!container) {
                return;
            }
            const quickSteps = [
                {
                    id: 'pipes/filter-items',
                    title: 'Filter',
                    description: 'Keep matching items'
                },
                {
                    id: 'pipes/limit-items',
                    title: 'Limit',
                    description: 'Take the first items'
                },
                {
                    id: 'pipes/output-debug',
                    title: 'Debug',
                    description: 'Inspect a value'
                }
            ].map((step) => ({ ...step, ability: abilityById(step.id) }))
                .filter((step) => step.ability);

            container.hidden = !quickSteps.length;
            if (!quickSteps.length) {
                container.innerHTML = '';
                return;
            }

            container.innerHTML = `
                <div class="meta">Common steps</div>
                <div class="quick-step-grid"></div>
            `;
            const grid = $('.quick-step-grid', container);
            for (const step of quickSteps) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'quick-step';
                button.draggable = true;
                button.innerHTML = '<strong></strong><span class="meta"></span>';
                $('strong', button).textContent = step.title;
                $('.meta', button).textContent = step.description;
                attachAbilityDrag(button, step.ability);
                button.addEventListener('click', () => addNode(step.ability));
                grid.append(button);
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
                button.addEventListener('click', () => loadExample(example).catch((error) => reportError(error.message)));
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
            $$('.input-popover', graph).forEach((popover) => popover.remove());
            $('[data-empty]').style.display = state.graph.nodes.length ? 'none' : 'block';

            for (const node of state.graph.nodes) {
                const ability = abilityById(node.ability_id);
                const element = document.createElement('article');
                element.className = `node ${node.id === state.selectedNodeId ? 'selected' : ''}`;
                element.dataset.nodeId = node.id;
                element.style.left = `${node.position.x}px`;
                element.style.top = `${node.position.y}px`;
                element.innerHTML = `
                    <button type="button" class="node-delete danger" data-node-delete aria-label="Remove node" title="Remove node">×</button>
                    <h2></h2>
                    <p class="meta"></p>
                    <div class="badge-row"></div>
                    <div data-node-debug-preview></div>
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
                `;
                $('h2', element).textContent = node.label || ability?.label || node.ability_id;
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
                const debugContainer = $('[data-node-debug-preview]', element);
                if (isDebugOutputNode(node)) {
                    const preview = document.createElement('pre');
                    const value = debugPreviewValue(node);
                    preview.className = `node-debug-preview ${value === undefined ? 'is-empty' : ''}`;
                    preview.textContent = value === undefined ? 'Run the pipe to inspect the bound value here.' : compactPreview(value);
                    debugContainer.append(preview);
                } else {
                    debugContainer.remove();
                }
                $('[data-node-delete]', element).addEventListener('click', (event) => {
                    event.stopPropagation();
                    removeNode(node.id);
                    render();
                });
                const inputPorts = $('[data-input-ports]', element);
                for (const port of inputPortsForNode(node)) {
                    const portRow = document.createElement('div');
                    portRow.className = 'port-row';
                    const input = document.createElement('button');
                    input.type = 'button';
                    input.className = 'port input-port';
                    input.dataset.portKind = 'input';
                    input.dataset.nodeId = node.id;
                    input.dataset.portName = port.name;
                    const inputBinding = (node.bindings || []).find((binding) => binding.target === port.name);
                    if (inputBinding) {
                        input.classList.add('bound');
                    }
                    if (node.id === state.selectedNodeId && state.activeBindingTarget === port.name) {
                        input.classList.add('active');
                    }
                    const manualPreview = inputBinding ? '' : inputValuePreview(node, port.name);
                    input.title = manualPreview ? `${port.title || port.label}: ${manualPreview}` : (port.title || port.label);
                    if (manualPreview) {
                        input.classList.add('has-value');
                    }
                    const label = document.createElement('span');
                    label.textContent = port.label;
                    input.append(label);
                    if (manualPreview) {
                        const preview = document.createElement('span');
                        preview.className = 'port-value';
                        preview.textContent = manualPreview;
                        input.append(preview);
                    }
                    input.addEventListener('click', (event) => {
                        event.stopPropagation();
                        if (completeConnectionDraft(node, port.name)) {
                            return;
                        }
                        state.selectedNodeId = node.id;
                        state.activeBindingTarget = port.name;
                        state.openListArg = `${node.id}.${port.name}`;
                        state.visualInputPopover = { nodeId: node.id, propName: port.name };
                        render();
                    });
                    portRow.append(input);
                    if (inputBinding) {
                        const clear = document.createElement('button');
                        clear.type = 'button';
                        clear.className = 'port-clear danger';
                        clear.setAttribute('aria-label', `Remove binding for ${port.name}`);
                        clear.title = `Remove binding for ${port.name}`;
                        clear.textContent = '×';
                        clear.addEventListener('click', (event) => {
                            event.stopPropagation();
                            removeBindingTarget(node, port.name);
                            render();
                        });
                        portRow.append(clear);
                    }
                    inputPorts.append(portRow);
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
                    if (state.connectionDraft?.sourceId === node.id && state.connectionDraft.path === (port.path || '')) {
                        output.classList.add('active');
                    }
                    output.title = port.title || (port.value ? `${port.label}: ${port.value}` : port.label);
                    output.textContent = port.label;
                    attachOutputPortDrag(output, node, port);
                    output.addEventListener('click', (event) => {
                        event.stopPropagation();
                        if (output.dataset.connectionDragged === 'true') {
                            event.preventDefault();
                            return;
                        }
                        startConnectionDraft(node.id, port.path, event);
                    });
                    outputPorts.append(output);
                }
                element.addEventListener('click', (event) => {
                    if (element.dataset.dragged === 'true') {
                        event.preventDefault();
                        return;
                    }
                    if (state.connectionDraft) {
                        cancelConnectionDraft();
                    }
                    state.visualInputPopover = null;
                    state.selectedNodeId = node.id;
                    render();
                });
                attachNodeDrag(element, node);
                graph.append(element);
            }

            renderVisualInputPopover(graph);
            renderEdges();
            window.requestAnimationFrame(updateOutOfViewIndicator);
        };

        const renderVisualInputPopover = (graph) => {
            const popoverState = state.visualInputPopover;
            if (!popoverState) {
                return;
            }
            const node = nodeById(popoverState.nodeId);
            if (!node) {
                state.visualInputPopover = null;
                return;
            }
            const ability = abilityById(node.ability_id);
            const props = schemaProperties(ability?.input_schema);
            const prop = props.find((candidate) => candidate.name === popoverState.propName);
            if (!prop) {
                state.visualInputPopover = null;
                return;
            }

            const popover = document.createElement('div');
            popover.className = 'input-popover';
            popover.innerHTML = `
                <div class="input-popover-header">
                    <strong class="input-popover-title"></strong>
                    <button type="button" class="input-popover-close" data-close-input-popover aria-label="Close input editor" title="Close">×</button>
                </div>
                <div class="list-args" data-visual-input-args></div>
            `;
            $('.input-popover-title', popover).textContent = `${node.label || ability?.label || node.ability_id}: ${prop.name}`;
            popover.addEventListener('click', (event) => {
                event.stopPropagation();
            });
            $('[data-close-input-popover]', popover).addEventListener('click', (event) => {
                event.stopPropagation();
                state.visualInputPopover = null;
                renderGraph();
            });

            graph.append(popover);
            renderListArgs(node, props, $('[data-visual-input-args]', popover), {
                propName: prop.name,
                forceOpen: true,
                hideTitle: true,
                scope: 'visual'
            });

            const graphRect = graph.getBoundingClientRect();
            const port = findInputPort(node.id, prop.name);
            const portRect = port?.getBoundingClientRect();
            const x = portRect ? portRect.right - graphRect.left + 18 : (node.position.x + 300);
            const y = portRect ? portRect.top - graphRect.top - 12 : node.position.y;
            popover.style.left = `${Math.max(16, Math.round(x))}px`;
            popover.style.top = `${Math.max(16, Math.round(y))}px`;
        };

        const renderListBuilder = () => {
            const container = $('[data-list-builder]');
            if (!state.graph.nodes.length) {
                container.innerHTML = '<div class="flow-list" data-flow-list><div class="empty">Add abilities from the ability tray or start here.</div></div>';
                renderListAddNode($('[data-flow-list]', container), 0);
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
                            <div class="flow-step-title-row">
                                <input class="flow-step-title" type="text" data-step-label>
                                <div class="flow-step-actions">
                                    <button type="button" data-step-action="up" aria-label="Move step up" title="Move step up">↑</button>
                                    <button type="button" data-step-action="down" aria-label="Move step down" title="Move step down">↓</button>
                                    <button type="button" class="danger" data-step-action="remove" aria-label="Remove step" title="Remove step">×</button>
                                </div>
                            </div>
                            <div class="meta"></div>
                            <div class="badge-row"></div>
                        </div>
                    </div>
                    <div class="list-args" data-list-args></div>
                    <div class="output-preview" data-output-preview hidden>
                        <div class="output-preview-header">
                            <strong class="output-preview-title" data-output-preview-title>Step output</strong>
                            <div class="output-preview-actions" data-output-preview-actions hidden>
                                <button type="button" data-run-from-preview>Run full pipe</button>
                            </div>
                        </div>
                        <div class="output-preview-empty" data-output-preview-empty hidden></div>
                        <pre hidden></pre>
                        <div class="rendered-output" data-rendered-preview hidden></div>
                    </div>
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
                renderListArgs(node, props, $('[data-list-args]', step), { scope: 'list' });
                renderOutputPreview(node, $('[data-output-preview]', step));
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
                    if (!action && event.target.closest('input, select, textarea, summary, .list-arg')) {
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
                renderListAddNode(list, index + 1);
            });
        };

        const renderListAddNode = (list, insertIndex = state.graph.nodes.length) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'flow-add';
            const isOpen = state.listAddOpen && state.listAddIndex === insertIndex;
            if (!isOpen) {
                const label = insertIndex >= state.graph.nodes.length ? 'Add next node' : 'Insert node here';
                wrapper.innerHTML = '<button class="flow-add-button" data-list-add-open><strong>+</strong><span></span></button>';
                $('span', wrapper).textContent = label;
                $('[data-list-add-open]', wrapper).addEventListener('click', () => {
                    state.listAddOpen = true;
                    state.listAddIndex = insertIndex;
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
                        const targetIndex = state.listAddIndex ?? state.graph.nodes.length;
                        state.listAddOpen = false;
                        state.listAddIndex = null;
                        state.listAddSearch = '';
                        addNode(ability, targetIndex);
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
                state.listAddIndex = null;
                state.listAddSearch = '';
                renderListBuilder();
            });
            renderResults();
            list.append(wrapper);
        };

        const renderOutputPreview = (node, container) => {
            if (!container) {
                return;
            }
            const pre = $('pre', container);
            const empty = $('[data-output-preview-empty]', container);
            const rendered = $('[data-rendered-preview]', container);
            const actions = $('[data-output-preview-actions]', container);
            if (empty) {
                empty.hidden = true;
                empty.textContent = '';
                empty.classList.remove('error');
            }
            if (rendered) {
                rendered.hidden = true;
                rendered.innerHTML = '';
            }
            if (actions) {
                actions.hidden = true;
                const runButton = $('[data-run-from-preview]', actions);
                if (runButton) {
                    runButton.onclick = (event) => {
                        event.preventDefault();
                        event.stopPropagation();
                        runPipe().catch((error) => reportError(error.message));
                    };
                }
            }
            pre.hidden = true;
            pre.textContent = '';
            const run = state.lastRunResults[node.id];
            if (!run) {
                container.hidden = false;
                $('[data-output-preview-title]', container).textContent = isOutputNode(node) ? 'Rendered output' : 'Step output';
                if (empty) {
                    empty.hidden = false;
                    if (state.runError) {
                        empty.classList.add('error');
                        empty.textContent = state.runError;
                    } else {
                        empty.textContent = 'Run the full pipe to inspect what this step sends to the next step.';
                    }
                }
                if (actions) {
                    actions.hidden = false;
                }
                return;
            }
            container.hidden = false;
            if (isOutputNode(node)) {
                const result = run.result;
                const value = result && typeof result === 'object' && Object.prototype.hasOwnProperty.call(result, 'value') ?
                    result.value :
                    undefined;
                const inputValue = run.input && Object.prototype.hasOwnProperty.call(run.input, 'value') ? run.input.value : undefined;
                $('[data-output-preview-title]', container).textContent = 'Rendered output';
                if (isDashboardOutputNode(node) && rendered) {
                    pre.hidden = true;
                    rendered.hidden = false;
                    if (node.ability_id === 'pipes/output-dashboard-text') {
                        rendered.innerHTML = '';
                        appendTextElement(rendered, 'p', stringifyGlueValue(value));
                    } else {
                        const columns = Array.isArray(ensureNodeArgs(node).columns) ? ensureNodeArgs(node).columns : [];
                        renderValueHtml(value, rendered, columns);
                    }
                    return;
                }
                pre.hidden = false;
                pre.textContent = `Input value:\n${compactPreview(inputValue)}\n\nRendered value:\n${compactPreview(value)}`;
                return;
            }
            $('[data-output-preview-title]', container).textContent = 'Step output';
            pre.hidden = false;
            pre.textContent = compactPreview(run.result);
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
            const removed = nodeById(nodeId);
            if (!removed) {
                return;
            }
            const removedIndex = state.graph.nodes.findIndex((candidate) => candidate.id === nodeId);
            if (state.connectionDraft?.sourceId === nodeId) {
                state.connectionDraft = null;
            }
            state.graph.nodes = state.graph.nodes.filter((candidate) => candidate.id !== nodeId);
            state.graph.edges = state.graph.edges.filter((edge) => edge.from !== nodeId && edge.to !== nodeId);
            for (const node of state.graph.nodes) {
                node.bindings = (node.bindings || []).filter((binding) => binding.source !== nodeId);
            }
            state.selectedNodeId = state.graph.nodes[Math.min(removedIndex, state.graph.nodes.length - 1)]?.id || null;
            syncEdgesFromBindings();
            clearRunResults();
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
            if (state.connectionDraft) {
                renderConnectionDraft(svg);
            }
            window.requestAnimationFrame(updateOutOfViewIndicator);
        };

        const renderConnectionDraft = (svg) => {
            const draft = state.connectionDraft;
            if (!draft?.pointer) {
                return;
            }
            const sourcePort = findOutputPort(draft.sourceId, draft.path || '');
            if (!sourcePort) {
                return;
            }
            drawEdge(
                svg,
                portPoint(sourcePort, 'output'),
                draft.pointer,
                false,
                'var(--pipes-accent)',
                0
            );
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
                    <div class="inspector-header">
                        <strong>Inspector</strong>
                        <button type="button" class="inspector-close" data-action="close-inspector" aria-label="Hide inspector" title="Hide inspector">×</button>
                    </div>
                    <div class="empty">Select a node to configure inputs and bindings.</div>
                `;
                $('[data-action="close-inspector"]', inspector).addEventListener('click', () => {
                    state.inspectorOpen = false;
                    renderBuilderMode();
                    renderEdges();
                });
                return;
            }
            const ability = abilityById(node.ability_id);
            const props = schemaProperties(ability?.input_schema);
            ensureActiveBindingTarget(node, props);
            inspector.innerHTML = `
                <div class="inspector-header">
                    <strong>Inspector</strong>
                    <button type="button" class="inspector-close" data-action="close-inspector" aria-label="Hide inspector" title="Hide inspector">×</button>
                </div>
                <div class="field">
                    <label>Node label</label>
                    <input type="text" data-node-label>
                </div>
                <div class="list-args" data-inspector-inputs></div>
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
                <div class="output-preview" data-inspector-output-preview hidden>
                    <div class="output-preview-header">
                        <strong class="output-preview-title" data-output-preview-title>Step output</strong>
                        <div class="output-preview-actions" data-output-preview-actions hidden>
                            <button type="button" data-run-from-preview>Run full pipe</button>
                        </div>
                    </div>
                    <div class="output-preview-empty" data-output-preview-empty hidden></div>
                    <pre hidden></pre>
                    <div class="rendered-output" data-rendered-preview hidden></div>
                </div>
                <button class="danger" data-action="remove-node">Remove Node</button>
            `;
            $('[data-action="close-inspector"]', inspector).addEventListener('click', () => {
                state.inspectorOpen = false;
                renderBuilderMode();
                renderEdges();
            });
            $('[data-node-label]', inspector).value = node.label || ability?.label || node.ability_id;
            $('[data-node-args]', inspector).value = JSON.stringify(ensureNodeArgs(node), null, 2);
            renderListArgs(node, props, $('[data-inspector-inputs]', inspector), { scope: 'inspector' });

            $('[data-node-label]', inspector).addEventListener('input', (event) => {
                node.label = event.target.value;
                markDirty();
                renderGraph();
            });
            $('[data-node-args]', inspector).addEventListener('change', (event) => {
                try {
                    node.args = normalizeArgs(JSON.parse(event.target.value || '{}'));
                    markDirty();
                    setStatus('Unsaved changes');
                } catch (error) {
                    reportError('Invalid JSON in node args');
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

            renderBindings(node, props);
            renderOutputPreview(node, $('[data-inspector-output-preview]', inspector));
        };

        const renderListArgs = (node, props, container, options = {}) => {
            if (!container) {
                return;
            }
            const visibleProps = options.propName ? props.filter((prop) => prop.name === options.propName) : props;
            if (!props.length) {
                container.innerHTML = '<div class="notice">No declared inputs.</div>';
                return;
            }
            if (!visibleProps.length) {
                container.innerHTML = '<div class="notice">Input is not declared by this ability.</div>';
                return;
            }

            container.innerHTML = options.hideTitle ? '' : '<strong>Inputs</strong>';
            ensureNodeArgs(node);
            for (const prop of visibleProps) {
                const binding = (node.bindings || []).find((candidate) => candidate.target === prop.name);
                const sourceNodes = compatibleBindingSourceNodesFor(node, prop);
                const argValue = ensureNodeArgs(node)[prop.name];
                const row = document.createElement('details');
                row.className = 'list-arg';
                row.dataset.listArgDetails = `${node.id}.${prop.name}`;
                row.open = !!options.forceOpen || state.openListArg === row.dataset.listArgDetails;
                if (node.ability_id === 'pipes/format-date-field' && ['value', 'path', 'date_format'].includes(prop.name)) {
                    row.open = true;
                }
                const summary = document.createElement('summary');
                summary.textContent = `${prop.name}${prop.required ? ' *' : ''}`;
                summary.addEventListener('click', () => {
                    state.selectedNodeId = node.id;
                    state.activeBindingTarget = prop.name;
                    state.openListArg = `${node.id}.${prop.name}`;
                    renderGraph();
                });
                row.append(summary);
                row.addEventListener('toggle', () => {
                    state.openListArg = row.open ? row.dataset.listArgDetails : '';
                    if (row.open) {
                        state.activeBindingTarget = prop.name;
                    }
                });

                if (node.ability_id === 'pipes/output-dashboard-list' && prop.name === 'columns') {
                    const hasConfiguredColumns = Array.isArray(ensureNodeArgs(node).columns);
                    const configuredColumns = hasConfiguredColumns ? ensureNodeArgs(node).columns : [];
                    const suggestions = dashboardListColumnSuggestionsFor(node);
                    const selectedColumns = hasConfiguredColumns ? configuredColumns : suggestions.slice(0, 6);
                    const choices = Array.from(new Set([...suggestions, ...configuredColumns]));
                    if (!choices.length) {
                        const notice = document.createElement('div');
                        notice.className = 'notice';
                        notice.textContent = 'Run the pipe to inspect table columns.';
                        row.append(notice);
                    } else {
                        const grid = document.createElement('div');
                        grid.className = 'checkbox-grid';
                        for (const column of choices) {
                            const label = document.createElement('label');
                            const checkbox = document.createElement('input');
                            const text = document.createElement('span');
                            checkbox.type = 'checkbox';
                            checkbox.checked = selectedColumns.includes(column);
                            text.textContent = column;
                            checkbox.addEventListener('change', (event) => {
                                const current = Array.isArray(ensureNodeArgs(node).columns) ? ensureNodeArgs(node).columns : suggestions.slice(0, 6);
                                if (event.target.checked) {
                                    ensureNodeArgs(node).columns = Array.from(new Set([...current, column]));
                                } else {
                                    ensureNodeArgs(node).columns = current.filter((candidate) => candidate !== column);
                                }
                                markDirty();
                                render();
                            });
                            label.append(checkbox, text);
                            grid.append(label);
                        }
                        row.append(grid);
                    }
                    if (prop.description) {
                        const description = document.createElement('div');
                        description.className = 'meta';
                        description.textContent = prop.description;
                        row.append(description);
                    }
                    container.append(row);
                    continue;
                }

                const mode = binding ? 'binding' : (isUserQueryArg(argValue) ? 'ask' : 'manual');
                const modeGroup = document.createElement('div');
                modeGroup.className = 'input-mode';
                const modeName = `input-mode-${options.scope || 'args'}-${node.id}-${prop.name}`;
                const setBindingMode = () => {
                    if (!sourceNodes.length) {
                        return;
                    }
                    const source = sourceNodes[0];
                    const sourcePorts = compatibleOutputPortsFor(source, prop);
                    node.bindings = node.bindings || [];
                    const existing = node.bindings.find((candidate) => candidate.target === prop.name);
                    const nextBinding = existing || { target: prop.name, source: source.id, path: sourcePorts[0]?.path || '' };
                    nextBinding.source = existing?.source || source.id;
                    nextBinding.path = sourcePorts.some((port) => port.path === nextBinding.path) ? nextBinding.path : (sourcePorts[0]?.path || '');
                    if (!existing) {
                        node.bindings.push(nextBinding);
                    }
                    delete ensureNodeArgs(node)[prop.name];
                    syncEdgesFromBindings();
                    markDirty();
                    render();
                };
                const setManualMode = () => {
                    node.bindings = node.bindings || [];
                    node.bindings = node.bindings.filter((candidate) => candidate.target !== prop.name);
                    if (isUserQueryArg(ensureNodeArgs(node)[prop.name])) {
                        delete node.args[prop.name];
                    }
                    syncEdgesFromBindings();
                    markDirty();
                    render();
                };
                const setAskMode = () => {
                    node.bindings = node.bindings || [];
                    node.bindings = node.bindings.filter((candidate) => candidate.target !== prop.name);
                    ensureNodeArgs(node)[prop.name] = {
                        __pipes_user_query: true,
                        question: `What should ${prop.name} be?`
                    };
                    syncEdgesFromBindings();
                    markDirty();
                    render();
                };
                const modeOptions = [
                    ['manual', 'Predefined', setManualMode],
                    ['ask', 'Ask user', setAskMode]
                ];
                if (sourceNodes.length || binding) {
                    modeOptions.push(['binding', 'Previous output', setBindingMode]);
                }
                for (const [value, label, handler] of modeOptions) {
                    const optionLabel = document.createElement('label');
                    const radio = document.createElement('input');
                    radio.type = 'radio';
                    radio.name = modeName;
                    radio.value = value;
                    radio.checked = mode === value;
                    optionLabel.addEventListener('click', (event) => {
                        event.stopPropagation();
                    });
                    radio.addEventListener('click', (event) => {
                        event.stopPropagation();
                    });
                    radio.addEventListener('change', (event) => {
                        event.stopPropagation();
                        if (radio.checked) {
                            handler();
                        }
                    });
                    optionLabel.append(radio, document.createTextNode(label));
                    modeGroup.append(optionLabel);
                }
                row.append(modeGroup);

                if (sourceNodes.length || binding) {
                    const bindSelect = document.createElement('select');
                    const chooseSource = document.createElement('option');
                    chooseSource.value = '';
                    chooseSource.textContent = 'Bind from previous output';
                    bindSelect.append(chooseSource);
                    const selectSources = [...sourceNodes];
                    const boundSource = binding ? nodeById(binding.source) : null;
                    if (boundSource && !selectSources.some((source) => source.id === boundSource.id)) {
                        selectSources.unshift(boundSource);
                    }
                    for (const source of selectSources) {
                        const option = document.createElement('option');
                        option.value = source.id;
                        option.textContent = source.label || source.ability_id;
                        bindSelect.append(option);
                    }
                    bindSelect.value = binding?.source || '';
                    bindSelect.addEventListener('change', (event) => {
                        if (!event.target.value) {
                            setManualMode();
                            return;
                        }
                        node.bindings = node.bindings || [];
                        const existing = node.bindings.find((candidate) => candidate.target === prop.name);
                        const sourcePorts = compatibleOutputPortsFor(nodeById(event.target.value) || {}, prop);
                        const nextBinding = existing || { target: prop.name, source: event.target.value, path: sourcePorts[0]?.path || '' };
                        nextBinding.source = event.target.value;
                        nextBinding.path = sourcePorts.some((port) => port.path === nextBinding.path) ? nextBinding.path : (sourcePorts[0]?.path || '');
                        if (!existing) {
                            node.bindings.push(nextBinding);
                        }
                        delete ensureNodeArgs(node)[prop.name];
                        syncEdgesFromBindings();
                        markDirty();
                        render();
                    });
                    row.append(bindSelect);
                }

                if (isUserQueryArg(ensureNodeArgs(node)[prop.name])) {
                    const question = document.createElement('input');
                    question.type = 'text';
                    question.value = node.args[prop.name].question || '';
                    question.placeholder = `Question for ${prop.name}`;
                    question.addEventListener('input', (event) => {
                        ensureNodeArgs(node)[prop.name].question = event.target.value;
                        markDirty();
                    });
                    row.append(question);
                    container.append(row);
                    continue;
                }

                if (binding) {
                    const source = nodeById(binding.source);
                    const pathSelect = document.createElement('select');
                    const ports = source ? compatibleOutputPortsFor(source, prop) : [];
                    if (!ports.some((port) => port.path === (binding.path || ''))) {
                        ports.unshift({ path: binding.path || '', label: pathLabel(binding.path || '') || 'result', value: 'bound', type: 'any' });
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

                if (prop.name === 'path') {
                    const suggestions = itemPathSuggestionsFor(node);
                    if (suggestions.length) {
                        const pathSelect = document.createElement('select');
                        const choose = document.createElement('option');
                        choose.value = '';
                        choose.textContent = 'Choose item field';
                        pathSelect.append(choose);
                        for (const suggestion of suggestions) {
                            const option = document.createElement('option');
                            option.value = suggestion;
                            option.textContent = suggestion;
                            pathSelect.append(option);
                        }
                        const pathInput = document.createElement('input');
                        pathInput.type = 'text';
                        pathInput.dataset.nodeArgNode = node.id;
                        pathInput.dataset.nodeArg = prop.name;
                        pathInput.dataset.nodeArgType = prop.type;
                        pathInput.value = formatArgValue(ensureNodeArgs(node)[prop.name]);
                        pathSelect.value = suggestions.includes(pathInput.value) ? pathInput.value : '';
                        pathSelect.addEventListener('change', (event) => {
                            pathInput.value = event.target.value;
                            ensureNodeArgs(node)[prop.name] = event.target.value;
                            markDirty();
                        });
                        pathInput.addEventListener('input', (event) => {
                            ensureNodeArgs(node)[prop.name] = event.target.value;
                            pathSelect.value = suggestions.includes(event.target.value) ? event.target.value : '';
                            markDirty();
                        });
                        row.append(pathSelect, pathInput);
                        container.append(row);
                        continue;
                    }
                    if ((node.bindings || []).some((candidate) => candidate.target === 'items')) {
                        const notice = document.createElement('div');
                        notice.className = 'notice';
                        notice.textContent = 'Run the pipe to inspect item fields.';
                        row.append(notice);
                    }
                }

                if (node.ability_id === 'pipes/format-date-field' && prop.name === 'date_format') {
                    const currentFormat = formatArgValue(ensureNodeArgs(node).date_format) || 'F j, Y';
                    const sourceValue = dateFormatterSampleValue(node);
                    const formatSelect = document.createElement('select');
                    for (const [format, label] of dateFormatChoices) {
                        const option = document.createElement('option');
                        option.value = format;
                        option.textContent = `${label} (${format})`;
                        formatSelect.append(option);
                    }
                    const customOption = document.createElement('option');
                    customOption.value = '__custom';
                    customOption.textContent = 'Custom format';
                    formatSelect.append(customOption);

                    const formatInput = document.createElement('input');
                    formatInput.type = 'text';
                    formatInput.dataset.nodeArgNode = node.id;
                    formatInput.dataset.nodeArg = prop.name;
                    formatInput.dataset.nodeArgType = prop.type;
                    formatInput.value = currentFormat;
                    formatInput.placeholder = 'F j, Y';

                    const preview = document.createElement('div');
                    preview.className = 'output-preview-empty';
                    const syncDateFormatPreview = () => {
                        const selected = dateFormatChoices.find(([format]) => format === formatInput.value);
                        formatSelect.value = selected ? selected[0] : '__custom';
                        const rendered = phpDatePreview(formatInput.value || 'F j, Y', sourceValue);
                        preview.textContent = rendered ?
                            `Preview: ${sourceValue} -> ${rendered}` :
                            `Preview unavailable for ${sourceValue}`;
                    };

                    formatSelect.value = dateFormatChoices.some(([format]) => format === currentFormat) ? currentFormat : '__custom';
                    formatSelect.addEventListener('change', (event) => {
                        if (event.target.value !== '__custom') {
                            formatInput.value = event.target.value;
                            ensureNodeArgs(node)[prop.name] = event.target.value;
                            syncDateFormatPreview();
                            markDirty();
                        }
                    });
                    formatInput.addEventListener('input', (event) => {
                        ensureNodeArgs(node)[prop.name] = event.target.value || 'F j, Y';
                        syncDateFormatPreview();
                        markDirty();
                    });
                    syncDateFormatPreview();
                    row.append(formatSelect, formatInput, preview);
                    if (prop.description) {
                        const description = document.createElement('div');
                        description.className = 'meta';
                        description.textContent = prop.description;
                        row.append(description);
                    }
                    container.append(row);
                    continue;
                }

                const value = ensureNodeArgs(node)[prop.name];
                const control = document.createElement(prop.type.includes('array') || prop.type.includes('object') ? 'textarea' : 'input');
                control.dataset.nodeArgNode = node.id;
                control.dataset.nodeArg = prop.name;
                control.dataset.nodeArgType = prop.type;
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
                            delete ensureNodeArgs(node)[prop.name];
                        } else {
                            ensureNodeArgs(node)[prop.name] = nextValue;
                        }
                        markDirty();
                    } catch (error) {
                        reportError(`Invalid ${prop.name} value`);
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
            renderRunOutput();
            renderUndoState();
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
        $('[data-action="save"]').addEventListener('click', () => savePipe().catch((error) => reportError(error.message)));
        $('[data-action="delete"]').addEventListener('click', () => deletePipe().catch((error) => reportError(error.message)));
        $('[data-action="run"]').addEventListener('click', () => runPipe().catch((error) => reportError(error.message)));
        $('[data-action="undo"]').addEventListener('click', undoLastChange);
        $('[data-action="toggle-inspector"]').addEventListener('click', () => {
            state.inspectorOpen = !state.inspectorOpen;
            renderBuilderMode();
            renderEdges();
        });
        $$('[data-builder-mode]').forEach((button) => {
            button.addEventListener('click', () => setBuilderMode(button.dataset.builderMode));
        });
        $('.canvas').addEventListener('dragover', (event) => {
            if (!dragHasAbility(event)) {
                return;
            }
            event.preventDefault();
            event.dataTransfer.dropEffect = 'copy';
            $('.canvas').classList.add('drag-target');
        });
        $('.canvas').addEventListener('dragleave', (event) => {
            if (!event.currentTarget.contains(event.relatedTarget)) {
                event.currentTarget.classList.remove('drag-target');
            }
        });
        $('.canvas').addEventListener('drop', (event) => {
            const ability = abilityById(abilityIdFromDrag(event));
            $('.canvas').classList.remove('drag-target');
            if (!ability) {
                return;
            }
            event.preventDefault();
            addNode(ability, state.graph.nodes.length, { position: droppedNodePosition(event) });
        });
        $('.canvas').addEventListener('wheel', scrollPageFromCanvasWheel, { passive: false });
        $('.canvas').addEventListener('scroll', updateOutOfViewIndicator, { passive: true });
        $('[data-out-of-view-indicator]').addEventListener('click', scrollToOutOfViewNode);
        $('[data-graph]').addEventListener('click', (event) => {
            if (!event.target.closest('.node, .input-popover')) {
                cancelConnectionDraft();
                state.visualInputPopover = null;
                renderGraph();
            }
        });
        document.addEventListener('pointermove', updateConnectionDraft);
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                cancelConnectionDraft();
            }
        });
        window.addEventListener('resize', updateOutOfViewIndicator);

        Promise.all([loadPipes(), loadAbilities(), loadExamples()])
            .then(() => {
                const pipeId = Number(new URLSearchParams(window.location.search).get('pipe') || 0);
                const draft = readLocalDraft();
                if (draft && (!pipeId || Number(draft.selectedPipeId || 0) === pipeId) && restoreLocalDraft(draft)) {
                    return null;
                }
                if (pipeId > 0) {
                    return loadPipe(pipeId, false).then(() => setStatus(`Loaded build ${config.build}`));
                }
                render();
                resetUndoHistory();
                setStatus(`Loaded build ${config.build}`);
                return null;
            })
            .catch((error) => reportError(error.message));
    })();
    </script>

    <?php wp_app_body_close(); ?>
</body>
</html>
