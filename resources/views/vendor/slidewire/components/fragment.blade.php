<div
    {{ $attributes
        ->merge(['data-fragment' => true])
        ->when($index !== null, fn ($attributeBag) => $attributeBag->merge(['data-fragment-index' => $index]))
        ->class(['slidewire-fragment']) }}
>
    {{ $slot }}
</div>
