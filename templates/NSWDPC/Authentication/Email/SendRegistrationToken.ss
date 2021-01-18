
<h1><%t NSWDPC\\Members\\Configuration.HI 'Hi' %> {$Member.FirstName}</h1>

<% if $Initial %>

<%-- this is the initial notification of activation --%>
<p><%t NSWDPC\\Members\\Configuration.THANKS_FOR_REGISTERING 'Thanks for registering at' %> {$SiteConfig.Title}</p>

<% end_if %>


<p><%t NSWDPC\\Members\\Configuration.COMPLETE_YOUR_REGISTRATION_RESEND 'This email contains instructions on how to complete your registration at' %> {$SiteConfig.Title}. <%t NSWDPC\\Members\\Configuration.PLEASE_FOLLOW_INSTRUCTIONS_BELOW 'Please follow the instructions below.' %></p>

<% if $Code %>

    <p><%t NSWDPC\\Members\\Configuration.WE_HAVE_RECEIVED_SHORT 'We have received a request to send you a registration code' %>.</p>

    <p><%t NSWDPC\\Members\\Configuration.ENTER_THE_FOLLOWING_CODE 'Enter the following code at the' %> <a href="$RegistrationCompletionLink"><%t NSWDPC\\Members\\Configuration.COMPLETE_REGISTRATION_PAGE 'Complete Registration Page' %></a>.</p>

    <p style="font-size:xxx-large;text-align: center;">{$Code.XML}</p>

    <% if $ProfileContactLink %>
        <%t NSWDPC\\Members\\Configuration.IF_NOT_INITIATED 'If this was not initiated by you, please ignore this email' %> <a href="$ProfileContactLink"><%t NSWDPC\\Members\\Configuration.OR_CONTACT_US 'or contact us for assistance' %></a>.</p>
    <% else %>
        <p><%t NSWDPC\\Members\\Configuration.GENERAL_UNEXPECTED_CODE_CTA 'If this was not initiated by you, please ignore this email or contact your website administrator' %></p>
    <% end_if %>

    <% if $RequireAdminApproval %>
        <%-- requires a code and admin approval --%>
        <p><%t NSWDPC\\Members\\Configuration.ALSO_REQUIRES_ADMIN_APPROVAL 'Your profile also requires administration approval. Once that approval is complete, you may access your profile' %>
    <% end_if %>

<% else_if $RequireAdminApproval %>

    <%-- requires admin approval --%>
    <p><%t NSWDPC\\Members\\Configuration.ALSO_REQUIRES_ADMIN_APPROVAL 'Your profile also requires administration approval. Once that approval is complete, you may access your profile' %>

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
