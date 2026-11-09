<x-mail::message>
# Permintaan Reset Password

Kami menerima permintaan untuk mereset password akun Anda.

Silakan klik tombol di bawah ini untuk membuat password baru:

<x-mail::button :url="$resetUrl" color="primary">
Reset Password
</x-mail::button>

Jika Anda tidak merasa meminta reset password, Anda dapat mengabaikan email ini.

Terima kasih,<br>
Tim {{ config('app.name') }}
</x-mail::message>
