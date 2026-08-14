@props(['name', 'class' => 'h-5 w-5'])

<i data-lucide="{{ $name }}" {{ $attributes->merge(['class' => $class]) }}></i>
