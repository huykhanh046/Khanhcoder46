@props(['title'])<div x-data="{ open: false }"><button type="button" class="text-link"
        @click="open=true;$nextTick(()=>$refs.dialog.showModal())">{{ $title }}</button>
    <dialog x-ref="dialog" @close="open=false" @click="$event.target===$refs.dialog && $refs.dialog.close()"
        class="m-auto p-8 max-w-lg w-full border border-gray-200 backdrop:bg-black/40">
        <h2 class="text-xl mb-5">{{ $title }}</h2>{{ $slot }}<button type="button"
            @click="$refs.dialog.close()" class="btn mt-5">Đóng</button>
    </dialog>
</div>
