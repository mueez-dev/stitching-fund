<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="{
        rating: $wire.entangle('{{ $getStatePath() }}').defer,
        hoverRating: 0,
        setRating(value) {
            this.rating = value;
            $wire.set('{{ $getStatePath() }}', value);
        },
        getStarColor(index) {
            const currentRating = this.hoverRating || this.rating || 5;
            if (index > currentRating) return '#d1d5db';
            if (currentRating < 4) return '#fb923c';
            if (currentRating === 4) return '#4b5563';
            return '#22c55e';
        },
        isFilled(index) {
            const currentRating = this.hoverRating || this.rating || 5;
            return index <= currentRating;
        }
    }" 
    x-init="rating = rating || 5"
    style="display: flex; align-items: center; gap: 2px;">
        @for ($i = 1; $i <= 5; $i++)
            <svg 
                xmlns="http://www.w3.org/2000/svg" 
                width="24"
                height="24"
                style="cursor: pointer; transition: all 0.2s ease; flex-shrink: 0;"
                :style="'color: ' + getStarColor({{ $i }})"
                :fill="isFilled({{ $i }}) ? 'currentColor' : 'none'"
                stroke="currentColor"
                stroke-width="1"
                viewBox="0 0 20 20"
                @click="setRating({{ $i }})"
                @mouseenter="hoverRating = {{ $i }}"
                @mouseleave="hoverRating = 0"
            >
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
            </svg>
        @endfor
        <span style="margin-left: 6px; font-size: 13px; font-weight: 500;" x-text="(hoverRating || rating || 5) + '/5'"></span>
    </div>
</x-dynamic-component>