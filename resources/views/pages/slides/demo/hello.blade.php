<?php

use Livewire\Component;

new class extends Component {
    //
}; ?>

<x-slidewire::deck>
    <x-slidewire::slide class="bg-slate-900 text-white">
        <div class="mx-auto max-w-5xl space-y-6">
            <h1 class="text-4xl font-bold tracking-tight">Hello SlideWire</h1>
            <p class="text-lg text-slate-200">
                Presentation key: <strong>demo/hello</strong>
            </p>
            <x-slidewire::fragment>
                <p class="text-base text-slate-300">Use the arrow keys, click, or swipe to navigate.</p>
            </x-slidewire::fragment>
        </div>
    </x-slidewire::slide>

    <x-slidewire::slide class="bg-slate-900 text-slate-200">
        <div class="mx-auto max-w-4xl space-y-6">
            <x-slidewire::markdown>
## Markdown Support

- Write markdown directly in this single presentation file.
- Combine markdown with Tailwind classes in each slide.
            </x-slidewire::markdown>
        </div>
    </x-slidewire::slide>
</x-slidewire::deck>
