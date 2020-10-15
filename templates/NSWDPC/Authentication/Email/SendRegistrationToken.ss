
    <h1><%t NSWDPC\\Members\\Configuration.HI 'Hi' %> {$Member.FirstName}</h1>

    <p><%t NSWDPC\\Members\\Configuration.WE_HAVE_RECEIVED 'We have received a request to send you a registration code. If this was not initiated by you, please ignore this email or contact your website administrator' %></p>

    <% if $Initial %>

    <%-- this is the initial notification of activation --%>
    <p><%t NSWDPC\\Members\\Configuration.THANKS_FOR_REGISTERING 'Thanks for registering at' %> {$SiteConfig.Title}</p>

    <% end_if %>

    <p><%t NSWDPC\\Members\\Configuration.COMPLETE_YOUR_REGISTRATION_RESEND 'This email contains instructions on how to complete your registration at' %> {$SiteConfig.Title}. <%t NSWDPC\\Members\\Configuration.COMPLETE_REGISTRATION_INFO_RESEND 'Please follow the instructions below.' %></p>

    <% if $Code %>
    <p><%t NSWDPC\\Members\\Configuration.ENTER_THE_FOLLOWING_CODE 'Enter the following code at the' %> <%t NSWDPC\\Members\\Configuration.COMPLETE_REGISTRATION_PAGE 'Complete Registration page' %>:</p>
        <p style="font-size:xxx-large;text-align: center;">$Code</p>
    <% end_if %>

    <% if $RequireAdminApproval %>
        <p><%t NSWDPC\\Members\\Configuration.ALSO_REQUIRES_ADMIN_APPROVAL 'Your profile also requires administration approval. Once that approval is complete, you may access your profile' %>
    <% end_if %>

    <p><a href="$RegistrationCompletionLink"><%t NSWDPC\\Members\\Configuration.COMPLETE_REGISTRATION 'Complete Registration' %></a></p>
