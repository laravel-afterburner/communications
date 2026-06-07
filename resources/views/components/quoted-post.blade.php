@props([
    'authorName',
    'body',
    'createdAt' => null,
    'bodyLimit' => 300,
])

<div {{ $attributes->merge(['class' => 'mb-2 flex flex-col gap-0 border-l-4 border-indigo-400 bg-indigo-50 py-1.5 pl-2 pr-2 text-sm dark:border-indigo-600 dark:bg-indigo-950/40']) }}>
    <p class="font-medium leading-tight text-indigo-900 dark:text-indigo-200">
        {{ __('Quoting :name', ['name' => $authorName]) }}
    </p>
    <p @class([
        'whitespace-pre-line leading-tight italic text-indigo-700 dark:text-indigo-300',
        'line-clamp-4' => $bodyLimit === 300,
        'line-clamp-3' => $bodyLimit === 200,
    ])>
        &ldquo;{{ trim(Str::limit($body, $bodyLimit)) }}&rdquo;
    </p>
    @if($createdAt)
        <p class="mt-1 text-right text-xs text-indigo-600/80 dark:text-indigo-400/80">
            {!! format_date_superscript($createdAt, 'datetime') !!}
        </p>
    @endif
</div>
