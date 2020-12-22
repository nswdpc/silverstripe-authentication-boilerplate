
<h1><%t NSWDPC\\Members\\Configuration.HI 'Hi' %> {$Member.FirstName}</h1>

<% if $Initial %>

<%-- this is the initial notification of activation --%>
<p><%t NSWDPC\\Members\\Configuration.THANKS_FOR_REGISTERING 'Thanks for registering at' %> {$SiteConfig.Title}</p>

<% end_if %>


<p><%t NSWDPC\\Members\\Configuration.COMPLETE_YOUR_REGISTRATION_RESEND 'This email contains instructions on how to complete your registration at' %> {$SiteConfig.Title}. <%t NSWDPC\\Members\\Configuration.PLEASE_FOLLOW_INSTRUCTIONS_BELOW 'Please follow the instructions below.' %></p>

<% if $Code %>

    <%-- requires a self verification code --%>
    <p><%t NSWDPC\\Members\\Configuration.WE_HAVE_RECEIVED 'We have received a request to send you a registration code. If this was not initiated by you, please ignore this email or contact your website administrator' %></p>

    <p><%t NSWDPC\\Members\\Configuration.ENTER_THE_FOLLOWING_CODE 'Enter the following code at the' %> <a href="$RegistrationCompletionLink"><%t NSWDPC\\Members\\Configuration.COMPLETE_REGISTRATION_PAGE 'Complete Registration Page' %></a></p>

    <p style="font-size:xxx-large;text-align: center;">{$Code.XML}</p>

    <% if $RequireAdminApproval %>
        <%-- requires a code and admin approval --%>
        <p><%t NSWDPC\\Members\\Configuration.ALSO_REQUIRES_ADMIN_APPROVAL 'Your profile also requires administration approval. Once that approval is complete, you may access your profile' %>
    <% end_if %>

<% else_if $RequireAdminApproval %>

    <%-- requires admin approval --%>
    <p><%t NSWDPC\\Members\\Configuration.ALSO_REQUIRES_ADMIN_APPROVAL 'Your profile also requires administration approval. Once that approval is complete, you may access your profile' %>

<% else %>

    <%-- requires neither a code nor approval (i.e no verification at all is set in config) --%>
    <p><%t NSWDPC\\Members\\Configuration.YOUR_PROFILE_IS_ENABLED 'Your profile is active, simply sign in to access your profile' %></p>

<% end_if %>
