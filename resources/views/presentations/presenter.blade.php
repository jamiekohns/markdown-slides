<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Presenter View</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --presenter-bg: #0a1320;
            --panel-bg: #f5f7fb;
            --panel-border: #d8dde8;
            --cue-active: #1f6feb;
            --script-font-size: 1rem;
        }

        html, body {
            margin: 0;
            width: 100%;
            height: 100%;
            background: var(--presenter-bg);
            overflow: hidden;
        }

        .presenter-layout {
            width: 100%;
            height: 100dvh;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
        }

        .presenter-stage {
            border-right: 1px solid rgb(148 163 184 / 25%);
            background: #020617;
        }

        .presenter-stage iframe {
            border: 0;
            width: 100%;
            height: 100%;
            display: block;
        }

        .teleprompter {
            display: grid;
            grid-template-rows: auto 1fr auto;
            background: var(--panel-bg);
            color: #0f172a;
            min-width: 0;
            min-height: 0;
            overflow: hidden;
        }

        .teleprompter[data-theme="dark"] {
            --panel-bg: #0f172a;
            --panel-border: #334155;
            color: #e2e8f0;
        }

        .teleprompter-scroll-region {
            min-height: 0;
            position: relative;
            overflow: hidden;
            height: 100%;
        }

        .teleprompter-header {
            padding: 1rem 1rem 0.75rem;
            border-bottom: 1px solid var(--panel-border);
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        }

        .teleprompter[data-theme="dark"] .teleprompter-header {
            background: linear-gradient(180deg, #111827 0%, #0b1220 100%);
        }

        .teleprompter-title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
        }

        .teleprompter-subtitle {
            margin: 0.35rem 0 0;
            color: #475569;
            font-size: 0.9rem;
        }

        .teleprompter[data-theme="dark"] .teleprompter-subtitle {
            color: #94a3b8;
        }

        .teleprompter-body {
            height: 100%;
            overflow: auto;
            padding: 1.25rem 1.25rem 3rem;
            scroll-behavior: smooth;
            min-height: 0;
            font-size: var(--script-font-size);
            line-height: 1.65;
            background:
                radial-gradient(circle at 100% 0, rgb(31 111 235 / 10%), transparent 50%),
                linear-gradient(180deg, #f9fbff 0%, #f1f5f9 100%);
        }

        .teleprompter[data-theme="dark"] .teleprompter-body {
            background:
                radial-gradient(circle at 100% 0, rgb(56 189 248 / 10%), transparent 50%),
                linear-gradient(180deg, #0f172a 0%, #020617 100%);
        }

        .script-size-controls {
            position: absolute;
            top: 0.65rem;
            right: 0.75rem;
            display: inline-flex;
            gap: 0.35rem;
            z-index: 26;
        }

        .script-size-controls button {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: rgb(255 255 255 / 95%);
            color: #0f172a;
            width: 2rem;
            height: 2rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            line-height: 1;
            padding: 0;
        }

        .teleprompter[data-theme="dark"] .script-size-controls button {
            border-color: #475569;
            background: rgb(15 23 42 / 95%);
            color: #e2e8f0;
        }

        .teleprompter-leading-space {
            height: calc(1.65rem * 9);
        }

        .teleprompter-trailing-space {
            height: calc(1.65rem * 30);
        }

        .teleprompter-trigger-box {
            position: absolute;
            left: 0.5rem;
            right: 1.25rem;
            top: calc(1.25rem + (1.65rem * 8));
            height: 2rem;
            border-bottom: 3px solid #ff0000;
            border-radius: 0;
            z-index: 20;
            pointer-events: none;
        }

        .teleprompter-section {
            background: #fff;
            border: 1px solid #d8dee8;
            border-radius: 12px;
            padding: 0.9rem 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 10px 25px -25px rgb(15 23 42 / 65%);
        }

        .teleprompter[data-theme="dark"] .teleprompter-section {
            background: #111827;
            border-color: #334155;
            color: #e2e8f0;
            box-shadow: 0 12px 28px -26px rgb(2 6 23 / 90%);
        }

        .teleprompter-section.is-active {
            border-color: var(--cue-active);
            box-shadow: 0 0 0 2px rgb(31 111 235 / 20%);
        }

        .teleprompter-controls {
            border-top: 1px solid var(--panel-border);
            padding: 0.85rem 1rem;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.5rem;
            background: #fff;
        }

        .teleprompter[data-theme="dark"] .teleprompter-controls {
            background: #0b1220;
        }

        .teleprompter-controls button {
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 0.55rem 0.6rem;
            font-weight: 600;
            background: #f8fafc;
            color: #0f172a;
        }

        .teleprompter[data-theme="dark"] .teleprompter-controls button {
            border-color: #475569;
            background: #111827;
            color: #e2e8f0;
        }

        .teleprompter-controls button[data-pressed="true"] {
            background: #0f172a;
            color: #fff;
            border-color: #0f172a;
        }

        @media (max-width: 1024px) {
            .presenter-layout {
                grid-template-columns: 1fr;
                grid-template-rows: 56dvh 44dvh;
            }

            .presenter-stage {
                border-right: 0;
                border-bottom: 1px solid rgb(148 163 184 / 25%);
            }
        }
    </style>
</head>
<body>
    <main class="presenter-layout" data-teleprompter-root>
        <section class="presenter-stage">
            <iframe src="{{ $presentationUrl }}" title="Presentation" data-presentation-frame></iframe>
        </section>

        <section class="teleprompter" data-teleprompter-panel data-theme="light">
            <header class="teleprompter-header">
                <h1 class="teleprompter-title">{{ $document->title }}</h1>
                <p class="teleprompter-subtitle">Scroll through script cues. Each cue boundary sends next/previous slide to the deck.</p>
            </header>

            <div class="teleprompter-scroll-region">
                <div class="script-size-controls" aria-label="Script font size controls">
                    <button type="button" data-font-decrease aria-label="Decrease script font size">A-</button>
                    <button type="button" data-font-increase aria-label="Increase script font size">A+</button>
                    <button type="button" data-theme-toggle aria-label="Toggle teleprompter theme">Dark</button>
                </div>
                <article class="teleprompter-body" data-script-viewport>
                    <div class="teleprompter-leading-space" aria-hidden="true"></div>
                    @foreach ($sections as $section)
                        <section class="teleprompter-section" data-cue-section data-cue-index="{{ $section['index'] }}">
                            {!! $section['html'] !!}
                        </section>
                    @endforeach
                    <div class="teleprompter-trailing-space" aria-hidden="true"></div>
                </article>
                <div class="teleprompter-trigger-box" data-trigger-box aria-hidden="true"></div>
            </div>

            <footer class="teleprompter-controls">
                <button type="button" data-scroll-step="-240" aria-label="Scroll up">Scroll up</button>
                <button type="button" data-scroll-step="240" aria-label="Scroll down">Scroll down</button>
                <button type="button" data-auto-scroll-toggle data-pressed="false" aria-label="Toggle auto-scroll">Auto scroll</button>
                <button type="button" data-open-deck aria-label="Open deck in new tab">Open deck</button>
            </footer>
        </section>
    </main>

    <script>
        (() => {
            const root = document.querySelector('[data-teleprompter-root]');
            if (!root) {
                return;
            }

            const viewport = root.querySelector('[data-script-viewport]');
            const frame = root.querySelector('[data-presentation-frame]');
            const sectionNodes = [...root.querySelectorAll('[data-cue-section]')];
            const triggerBox = root.querySelector('[data-trigger-box]');
            const autoToggle = root.querySelector('[data-auto-scroll-toggle]');
            const openDeckButton = root.querySelector('[data-open-deck]');
            const stepButtons = [...root.querySelectorAll('[data-scroll-step]')];
            const increaseFontButton = root.querySelector('[data-font-increase]');
            const decreaseFontButton = root.querySelector('[data-font-decrease]');
            const teleprompterPanel = root.querySelector('[data-teleprompter-panel]');
            const themeToggleButton = root.querySelector('[data-theme-toggle]');

            if (!viewport || !frame || sectionNodes.length === 0 || !triggerBox || !autoToggle || !openDeckButton || !increaseFontButton || !decreaseFontButton || !teleprompterPanel || !themeToggleButton) {
                return;
            }

            let activeCueIndex = 0;
            let currentSection = sectionNodes[0];
            let autoScrollTimer = null;
            let autoScrollEnabled = false;
            let lastScrollTop = 0;
            let scrollDirection = 'down';
            let scrollFrameRequested = false;
            let scriptFontSize = 1;
            const fontSizeStorageKey = 'slidewire.presenter.scriptFontSize';
            const themeStorageKey = 'slidewire.presenter.teleprompterTheme';

            const setSectionActive = (node) => {
                sectionNodes.forEach((section) => section.classList.toggle('is-active', section === node));
            };

            const sendDeckNavigation = (direction) => {
                const win = frame.contentWindow;
                if (!win || !win.document) {
                    return;
                }

                const ariaLabel = direction === 'next' ? 'Next slide' : 'Previous slide';
                const button = win.document.querySelector(`button[aria-label="${ariaLabel}"]`);
                if (!button) {
                    return;
                }

                button.click();
            };

            const handleCueChange = (nextNode) => {
                const nextCueIndex = Number(nextNode.dataset.cueIndex);
                if (Number.isNaN(nextCueIndex) || nextCueIndex === activeCueIndex) {
                    return;
                }

                const movingForward = nextCueIndex > activeCueIndex;
                activeCueIndex = nextCueIndex;
                setSectionActive(nextNode);

                sendDeckNavigation(movingForward ? 'next' : 'previous');
            };

            setSectionActive(currentSection);

            const triggerLineY = () => {
                const rect = triggerBox.getBoundingClientRect();

                return rect.top + (rect.height / 2);
            };

            const updateCueFromTrigger = () => {
                const y = triggerLineY();
                let bestNode = sectionNodes[0];
                let bestDistance = Number.POSITIVE_INFINITY;

                sectionNodes.forEach((node) => {
                    const rect = node.getBoundingClientRect();
                    const distance = y - rect.top;

                    if (distance >= 0 && distance < bestDistance) {
                        bestNode = node;
                        bestDistance = distance;
                    }
                });

                if (bestNode !== currentSection) {
                    currentSection = bestNode;
                    handleCueChange(bestNode);
                }
            };

            const scheduleCueUpdate = () => {
                if (scrollFrameRequested) {
                    return;
                }

                scrollFrameRequested = true;
                window.requestAnimationFrame(() => {
                    scrollFrameRequested = false;
                    updateCueFromTrigger();
                });
            };

            updateCueFromTrigger();

            viewport.addEventListener('scroll', () => {
                scrollDirection = viewport.scrollTop >= lastScrollTop ? 'down' : 'up';
                lastScrollTop = viewport.scrollTop;
                scheduleCueUpdate();
            }, { passive: true });

            window.addEventListener('resize', scheduleCueUpdate, { passive: true });

            const setScriptFontSize = (value) => {
                scriptFontSize = Math.max(0.85, Math.min(3, value));
                root.style.setProperty('--script-font-size', `${scriptFontSize.toFixed(2)}rem`);

                try {
                    window.localStorage.setItem(fontSizeStorageKey, scriptFontSize.toFixed(2));
                } catch {
                    // Ignore storage errors (private mode/quota).
                }

                scheduleCueUpdate();
            };

            const setTeleprompterTheme = (theme) => {
                const normalized = theme === 'dark' ? 'dark' : 'light';
                teleprompterPanel.dataset.theme = normalized;
                themeToggleButton.textContent = normalized === 'dark' ? '🔆' : '🌙';

                try {
                    window.localStorage.setItem(themeStorageKey, normalized);
                } catch {
                    // Ignore storage errors (private mode/quota).
                }
            };

            try {
                const stored = Number(window.localStorage.getItem(fontSizeStorageKey));
                if (!Number.isNaN(stored) && stored > 0) {
                    setScriptFontSize(stored);
                }
            } catch {
                // Ignore storage read errors.
            }

            try {
                const savedTheme = window.localStorage.getItem(themeStorageKey);
                setTeleprompterTheme(savedTheme === 'dark' ? 'dark' : 'light');
            } catch {
                setTeleprompterTheme('light');
            }

            increaseFontButton.addEventListener('click', () => {
                setScriptFontSize(scriptFontSize + 0.1);
            });

            decreaseFontButton.addEventListener('click', () => {
                setScriptFontSize(scriptFontSize - 0.1);
            });

            themeToggleButton.addEventListener('click', () => {
                const nextTheme = teleprompterPanel.dataset.theme === 'dark' ? 'light' : 'dark';
                setTeleprompterTheme(nextTheme);
            });

            stepButtons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    const step = Number(btn.dataset.scrollStep || '0');
                    viewport.scrollBy({ top: step, behavior: 'smooth' });
                });
            });

            autoToggle.addEventListener('click', () => {
                autoScrollEnabled = !autoScrollEnabled;
                autoToggle.dataset.pressed = autoScrollEnabled ? 'true' : 'false';
                autoToggle.textContent = autoScrollEnabled ? 'Pause auto' : 'Auto scroll';

                if (!autoScrollEnabled) {
                    if (autoScrollTimer !== null) {
                        window.clearInterval(autoScrollTimer);
                        autoScrollTimer = null;
                    }

                    return;
                }

                autoScrollTimer = window.setInterval(() => {
                    const maxTop = viewport.scrollHeight - viewport.clientHeight;
                    if (viewport.scrollTop >= maxTop) {
                        autoScrollEnabled = false;
                        autoToggle.dataset.pressed = 'false';
                        autoToggle.textContent = 'Auto scroll';
                        window.clearInterval(autoScrollTimer);
                        autoScrollTimer = null;

                        return;
                    }

                    viewport.scrollBy({ top: 1, behavior: 'auto' });
                }, 28);
            });

            openDeckButton.addEventListener('click', () => {
                window.open(frame.src, '_blank', 'noopener');
            });
        })();
    </script>
</body>
</html>
