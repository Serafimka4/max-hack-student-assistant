@props(['match'])
<span class="inline-flex gap-[3px]" aria-hidden="true">
    @foreach ($match as $row)
        <i @class([
            'size-2.5 rounded-[3px] border-[1.5px] border-ink',
            'bg-ink' => $row['state'] === 'ok',
            'bg-[linear-gradient(135deg,var(--color-ink)_50%,transparent_50%)]' => $row['state'] === 'gap',
        ])></i>
    @endforeach
</span>
