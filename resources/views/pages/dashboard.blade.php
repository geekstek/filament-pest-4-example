<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
        @foreach($this->getStats() as $stat)
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ $stat['label'] }}
                </div>
                <div class="mt-1 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                    {{ $stat['value'] }}
                </div>
                <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $stat['description'] }}
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h3 class="text-lg font-semibold text-gray-950 dark:text-white">Recent Posts</h3>
            <ul class="mt-4 space-y-3">
                @forelse($this->getRecentPosts() as $post)
                    <li class="flex items-center justify-between">
                        <div>
                            <span class="font-medium text-gray-950 dark:text-white">{{ $post->title }}</span>
                            @if($post->category)
                                <span class="ml-2 rounded-full bg-primary-100 px-2 py-0.5 text-xs text-primary-700 dark:bg-primary-900 dark:text-primary-300">
                                    {{ $post->category->name }}
                                </span>
                            @endif
                        </div>
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $post->created_at->diffForHumans() }}
                        </span>
                    </li>
                @empty
                    <li class="text-gray-500 dark:text-gray-400">No posts yet.</li>
                @endforelse
            </ul>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h3 class="text-lg font-semibold text-gray-950 dark:text-white">Pending Comments</h3>
            <ul class="mt-4 space-y-3">
                @forelse($this->getPendingComments() as $comment)
                    <li class="border-b border-gray-100 pb-3 last:border-b-0 dark:border-gray-800">
                        <div class="font-medium text-gray-950 dark:text-white">{{ $comment->author_name }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            on "{{ Str::limit($comment->post->title, 30) }}"
                        </div>
                        <div class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                            {{ Str::limit($comment->content, 100) }}
                        </div>
                    </li>
                @empty
                    <li class="text-gray-500 dark:text-gray-400">No pending comments.</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-filament-panels::page>
