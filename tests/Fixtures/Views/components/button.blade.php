<button type="{{ $attributes->get('type', 'button') }}" {{ $attributes->except('type') }}>{{ $slot }}</button>
