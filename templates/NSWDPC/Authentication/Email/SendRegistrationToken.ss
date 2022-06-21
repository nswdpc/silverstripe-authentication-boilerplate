
<h1><%t NSWDPC\\Members\\Configuration.HI_MEMBERNAME 'Hi {memberName}' memberName=$Member.FirstName %></h1>

<% if $Initial %>

<%-- this is the initial notification of activation --%>
<p><%t NSWDPC\\Members\\Configuration.THANKS_FOR_REGISTERING_AT_SITENAME 'Thanks for registering at {siteName}' siteName=$SiteConfig.Title %></p>

<p><%t NSWDPC\\Members\\Configuration.PENDING_PROFILE_TOKEN_SUMMARY 'This email contains important instructions to help you complete your registration at {siteName}. Please follow the instructions below.' siteName=$SiteConfig.Title %></p>
<% end_if %>

<% if $Code %>

    <p>
        <%t NSWDPC\\Members\\Configuration.PENDING_PROFILE_CODE_INSTRUCTIONS 'We have received a request to send you a one-time profile verification code. This is a once-only verification process and is separate to Multi-factor Authentication (MFA/2FA).' %>
    </p>

    <p>
        <%t NSWDPC\\Members\\Configuration.ENTER_THE_FOLLOWING_CODE 'When asked to verify your profile, please enter the following number in the field provided:' %>
    </p>

    <p style="font-size:xxx-large;text-align: center;"><span class="code">{$Code.XML}</span></p>

    <% if $RequireAdminApproval %>
        <%-- requires a code and admin approval --%>
        <p><%t NSWDPC\\Members\\Configuration.ALSO_REQUIRES_ADMIN_APPROVAL 'Your registration also requires approval by a {siteName} administrator. Once an administrator has approved your request to register, you may access your profile.' siteName=$SiteConfig.Title %>
    <% end_if %>

    <hr>

    <h3><%t NSWDPC\\Members\\Configuration.PENDING_PROFILE_HELP_HEADING 'Get assistance' %></h3>
    <% if $ProfileContactLink %>
        <%t NSWDPC\\Members\\Configuration.IF_NOT_INITIATED 'If this request was not initiated by you, please ignore this email' %> <a href="{$ProfileContactLink}"><%t NSWDPC\\Members\\Configuration.OR_CONTACT_US 'or contact us for assistance' %></a>.</p>
    <% else %>
        <p><%t NSWDPC\\Members\\Configuration.GENERAL_UNEXPECTED_CODE_CTA 'If this request was not initiated by you, please ignore this email or contact your website administrator' %>.</p>
    <% end_if %>

<% else_if $RequireAdminApproval %>

    <p style="font-size: large;"><%t NSWDPC\\Members\\Configuration.PENDING_PROFILE_TOKEN_SUMMARY 'This email contains important instructions to help you complete your registration at {siteName}. Please follow the instructions below.' siteName=$SiteConfig.Title %></p>

    <%-- requires admin approval --%>
    <p><%t NSWDPC\\Members\\Configuration.PROFILE_REQUIRES_ADMIN_APPROVAL 'Your registration requires approval by a {siteName} administrator. Once an administrator has approved your request to register, you may access your profile.' siteName=$SiteConfig.Title %>

<% else %>

    <%-- requires neither a code nor approval (i.e no verification at all is set in config) --%>
    <p>
        <% if $MemberProfileSignInLink %><a href="{$MemberProfileSignInLink.XML}"><% end_if %>
        <%t NSWDPC\\Members\\Configuration.YOUR_PROFILE_IS_ENABLED 'Your profile is active, sign in to access your profile' %>
        <% if $MemberProfileSignInLink %></a><% end_if %>
    </p>

<% end_if %>

<% if $ProfileContactLink %>
<p><a href="{$ProfileContactLink.XML}"><%t NSWDPC\\Members\\Configuration.NOTIFY_PROFILE_CONTACT 'Contact us if you need assistance' %></a></p>
<% end_if %>
