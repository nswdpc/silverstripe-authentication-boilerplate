<p>
    <% if $MFARequired %>
        This website requires you to use multi factor authentication (MFA) to protect your account.
    <% else %>
        You can use multi factor authentication (MFA) on this website to protect your account.
    <% end_if %>
    MFA is sometimes referred to as Two Factor Authentication (2FA).
</p>

<p>
    Your passwords can become known to third parties through many methods,
    including using a common password, re-using the same password on multiple websites,
    or by having your password stolen.
<p>

<p>
    When MFA is turned on for your account, you will be asked for your password and a code provided by an
    authentication application.
</p>

<p>
    Anyone who gains access to your password will not be able to gain access to your account
    unless they also have access to your authentication application.
<p>

<p>
    The setup process only takes a minute or two.
</p>

<p>
    To set up your authentication application, you will first need to install your preferred
    application on your device. There are many MFA applications, the following list shows
    the most popular:
</p>

<table class="table">
    <thead>
        <tr>
            <th>Application</th>
            <th>Compatibility</th>
            <th>Link</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Authy</td>
            <td>Android, iOS, Mac, Windows, Linux</td>
            <td><a href="https://authy.com/download/">Authy Download</a></td>
        </tr>
        <tr>
            <td>Google Authenticator</td>
            <td>Android, iOS</td>
            <td>
                <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2">for Android</a>
                <br>
                <a href="https://apps.apple.com/app/google-authenticator/id388497605">for iPhone and iPad</a>
            </td>
        </tr>
        <tr>
            <td>Auth0 Guardian</td>
            <td>Android, iPhone</td>
            <td>
                <a href="https://play.google.com/store/apps/details?id=com.auth0.guardian">for Android</a>
                <br>
                <a href="https://apps.apple.com/us/app/auth0-guardian/id1093447833">for iPhone</a>
            </td>
        </tr>
    </tbody>
</table>

<p>
    Once you have installed the application, follow the instructions it provides for adding a new account.
</p>

<p>
    This will involve pointing your device camera when prompted at a QR code shown on this website during
    the setup process or entering a code if your device does not have a camera.
</p>

<p>
    Once you have created the account in the application, this website will prompt you to enter a code shown
    on your device to complete the process.
</p>

<p>
    When the setup process is completed, on subsequent logins you will be asked to provide a code from the
    MFA application on your device.
</p>

<p>
    Be aware that if you lose your device or reset it, your settings will be lost. To avoid this happening,
    save the backup codes provided during the setup process in a safe place or use an MFA application that
    provides backups.
</p>

<p>
    If you are locked out of your account due to the incorrect code or password being entered, you will need to
    contact the owner of this website using the contact details provided.
</p>
