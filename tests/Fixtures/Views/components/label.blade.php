@props(['value' => null, 'for' => null])

<label {{ $attributes }}>{{ $value ?? $slot }}</label>
