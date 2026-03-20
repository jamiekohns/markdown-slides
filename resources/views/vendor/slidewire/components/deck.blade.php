<section {{ $attributes
    ->when($theme !== null, fn ($bag) => $bag->merge(['data-theme' => $theme]))
    ->when($transition !== null, fn ($bag) => $bag->merge(['data-transition' => $transition]))
    ->when($transitionSpeed !== null, fn ($bag) => $bag->merge(['data-transition-speed' => $transitionSpeed]))
    ->when($transitionDuration !== null, fn ($bag) => $bag->merge(['data-transition-duration' => $transitionDuration]))
    ->when($autoSlide !== null, fn ($bag) => $bag->merge(['data-auto-slide' => $autoSlide]))
    ->when($autoSlidePauseOnInteraction !== null, fn ($bag) => $bag->merge(['data-auto-slide-pause-on-interaction' => $autoSlidePauseOnInteraction]))
    ->when($showControls !== null, fn ($bag) => $bag->merge(['data-show-controls' => $showControls]))
    ->when($showProgress !== null, fn ($bag) => $bag->merge(['data-show-progress' => $showProgress]))
    ->when($showFullscreenButton !== null, fn ($bag) => $bag->merge(['data-show-fullscreen-button' => $showFullscreenButton]))
    ->when($keyboard !== null, fn ($bag) => $bag->merge(['data-keyboard' => $keyboard]))
    ->when($touch !== null, fn ($bag) => $bag->merge(['data-touch' => $touch]))
    ->when($highlightTheme !== null, fn ($bag) => $bag->merge(['data-highlight-theme' => $highlightTheme]))
    ->class(['slidewire-deck']) }}>
    {{ $slot }}
</section>
