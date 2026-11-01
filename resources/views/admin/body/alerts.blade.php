{{-- Toastr messages and SweetAlert confirmations for every admin page --}}
<script>
toastr.options = { closeButton: true, progressBar: true, positionClass: 'toast-top-right', timeOut: 4000 };

@if(session('message'))
    @php $toastType = in_array(session('alert-type'), ['success', 'error', 'warning', 'info'], true) ? session('alert-type') : 'info'; @endphp
    toastr[@json($toastType)](@json(session('message')));
@endif
@if(session('status'))
    @php $statusText = is_string(session('status')) ? session('status') : 'Saved'; @endphp
    toastr.success(@json($statusText));
@endif
@foreach($errors->all() as $error)
    toastr.error(@json($error));
@endforeach

// Ask before a risky action: add data-confirm="Question?" to a form or a submit button.
document.addEventListener('submit', function (e) {
    const form = e.target;
    const button = e.submitter;
    const question = (button && button.dataset.confirm) || form.dataset.confirm;
    if (!question || form.dataset.confirmed === '1') return;

    e.preventDefault();
    Swal.fire({
        title: 'Are you sure?',
        text: question,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#c45c26',
        cancelButtonColor: '#6c757d',
        confirmButtonText: (button && button.dataset.confirmButton) || 'Yes, do it',
        cancelButtonText: 'Cancel',
    }).then(function (result) {
        if (!result.isConfirmed) return;
        form.dataset.confirmed = '1';
        if (button && button.name) {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = button.name;
            hidden.value = button.value;
            form.appendChild(hidden);
        }
        form.submit();
    });
});
</script>
