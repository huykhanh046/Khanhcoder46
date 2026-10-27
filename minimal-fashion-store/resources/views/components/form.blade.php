@props(['action', 'method' => 'POST', 'confirm' => null])<form action="{{ $action }}" method="{{ strtoupper($method) === 'GET' ? 'GET' : 'POST' }}"
    {{ $attributes }} @if ($confirm) data-confirm="{{ $confirm }}" @endif>
    @if (strtoupper($method) !== 'GET')@csrf @if (!in_array(strtoupper($method), ['GET', 'POST']))
            @method($method)
        @endif
    @endif{{ $slot }}
</form>
