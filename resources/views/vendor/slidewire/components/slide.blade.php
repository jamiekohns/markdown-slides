<article
    {{ $attributes
        ->merge(['data-transition' => $transition])
        ->when($transitionSpeed !== null, fn ($attributeBag) => $attributeBag->merge(['data-transition-speed' => $transitionSpeed]))
        ->when($background !== null, fn ($attributeBag) => $attributeBag->merge(['data-background' => $background]))
        ->when($backgroundImage !== null, fn ($attributeBag) => $attributeBag->merge(['data-background-image' => $backgroundImage]))
        ->when($backgroundVideo !== null, fn ($attributeBag) => $attributeBag->merge(['data-background-video' => $backgroundVideo]))
        ->when($backgroundVideoLoop !== null, fn ($attributeBag) => $attributeBag->merge(['data-background-video-loop' => $backgroundVideoLoop]))
        ->when($backgroundVideoMuted !== null, fn ($attributeBag) => $attributeBag->merge(['data-background-video-muted' => $backgroundVideoMuted]))
        ->when($backgroundSize !== null, fn ($attributeBag) => $attributeBag->merge(['data-background-size' => $backgroundSize]))
        ->when($backgroundPosition !== null, fn ($attributeBag) => $attributeBag->merge(['data-background-position' => $backgroundPosition]))
        ->when($backgroundRepeat !== null, fn ($attributeBag) => $attributeBag->merge(['data-background-repeat' => $backgroundRepeat]))
        ->when($backgroundOpacity !== null, fn ($attributeBag) => $attributeBag->merge(['data-background-opacity' => $backgroundOpacity]))
        ->when($backgroundTransition !== null, fn ($attributeBag) => $attributeBag->merge(['data-background-transition' => $backgroundTransition]))
        ->when($autoAnimate !== null, fn ($attributeBag) => $attributeBag->merge(['data-auto-animate' => $autoAnimate]))
        ->when($autoAnimateDuration !== null, fn ($attributeBag) => $attributeBag->merge(['data-auto-animate-duration' => $autoAnimateDuration]))
        ->when($autoAnimateEasing !== null, fn ($attributeBag) => $attributeBag->merge(['data-auto-animate-easing' => $autoAnimateEasing]))
        ->when($autoSlide !== null, fn ($attributeBag) => $attributeBag->merge(['data-auto-slide' => $autoSlide]))
        ->when($theme !== null, fn ($attributeBag) => $attributeBag->merge(['data-theme' => $theme]))
        ->class(['slidewire-slide', 'slidewire-transition-'.$transition]) }}
>
    {{ $slot }}
</article>
