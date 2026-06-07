@props([
    'post',
])

@php
    use Afterburner\Communications\Enums\DiscussionPostReactionType;
    use Afterburner\Communications\Support\DiscussionReactionEligibility;

    $user = auth()->user();
    $canReact = DiscussionReactionEligibility::canReact($user, $post);
    $reactionDenialMessage = DiscussionReactionEligibility::denialMessage($user, $post);
    $currentUserReaction = auth()->id()
        ? $post->reactions->firstWhere('user_id', auth()->id())
        : null;

    $upReactions = $post->reactions->where('type', DiscussionPostReactionType::Up);
    $downReactions = $post->reactions->where('type', DiscussionPostReactionType::Down);

    $upNames = $upReactions->map(fn ($reaction) => $reaction->user->name)->values()->all();
    $downNames = $downReactions->map(fn ($reaction) => $reaction->user->name)->values()->all();

    $upActive = $currentUserReaction?->type === DiscussionPostReactionType::Up;
    $downActive = $currentUserReaction?->type === DiscussionPostReactionType::Down;

    $activeReactionClass = 'text-amber-500 bg-amber-50 dark:text-amber-400 dark:bg-amber-950/40';
    $inactiveReactionClass = 'text-gray-400 hover:text-amber-500 hover:bg-amber-50 dark:hover:text-amber-400 dark:hover:bg-amber-950/30';

    $upButtonClass = $upActive ? $activeReactionClass : $inactiveReactionClass;
    $downButtonClass = $downActive ? $activeReactionClass : $inactiveReactionClass;
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-3']) }}>
    <div class="inline-flex items-center gap-1">
        @if ($canReact)
            <button
                type="button"
                wire:click="toggleReaction({{ $post->id }}, 'up')"
                wire:loading.attr="disabled"
                wire:target="toggleReaction"
                class="inline-flex rounded p-0.5 transition {{ $upButtonClass }}"
                title="{{ __('Thumbs up') }}"
            >
                <span class="sr-only">{{ __('Thumbs up') }}</span>
                <x-afterburner-communications::reaction-icon type="up" />
            </button>
        @elseif ($reactionDenialMessage)
            <x-afterburner-communications::disabled-reaction-icon
                type="up"
                :message="$reactionDenialMessage"
            />
        @else
            <span class="inline-flex p-0.5 text-gray-400 dark:text-gray-500" aria-hidden="true">
                <x-afterburner-communications::reaction-icon type="up" />
            </span>
        @endif

        @if ($upReactions->isNotEmpty())
            <x-afterburner-communications::reaction-tooltip
                :names="$upNames"
                :label="__(':count thumbs up', ['count' => $upReactions->count()])"
            />
        @else
            <span class="inline-flex min-w-[1ch] tabular-nums text-gray-400 dark:text-gray-500">0</span>
        @endif
    </div>

    <div class="inline-flex items-center gap-1">
        @if ($canReact)
            <button
                type="button"
                wire:click="toggleReaction({{ $post->id }}, 'down')"
                wire:loading.attr="disabled"
                wire:target="toggleReaction"
                class="inline-flex rounded p-0.5 transition {{ $downButtonClass }}"
                title="{{ __('Thumbs down') }}"
            >
                <span class="sr-only">{{ __('Thumbs down') }}</span>
                <x-afterburner-communications::reaction-icon type="down" />
            </button>
        @elseif ($reactionDenialMessage)
            <x-afterburner-communications::disabled-reaction-icon
                type="down"
                :message="$reactionDenialMessage"
            />
        @else
            <span class="inline-flex p-0.5 text-gray-400 dark:text-gray-500" aria-hidden="true">
                <x-afterburner-communications::reaction-icon type="down" />
            </span>
        @endif

        @if ($downReactions->isNotEmpty())
            <x-afterburner-communications::reaction-tooltip
                :names="$downNames"
                :label="__(':count thumbs down', ['count' => $downReactions->count()])"
            />
        @else
            <span class="inline-flex min-w-[1ch] tabular-nums text-gray-400 dark:text-gray-500">0</span>
        @endif
    </div>
</div>
