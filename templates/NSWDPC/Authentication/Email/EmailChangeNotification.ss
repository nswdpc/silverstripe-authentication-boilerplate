
<h1><%t NSWDPC\\Authentication\\Services\\ConfigurationService.HI 'Hi' %> {$Member.FirstName}</h1>


<% if $SendToPrevious %>
<p>
    <%t NSWDPC\\Authentication\\Services\\ConfigurationService.YOUR_EMAIL_UPDATED_FROM_TO 'Your {website} email address was changed to {otherEmail}' website=$SiteConfig.Title otherEmail=$OtherEmail %>
</p>
<% else %>
<p>
    <%t NSWDPC\\Authentication\\Services\\ConfigurationService.YOUR_EMAIL_UPDATED_TO_FROM 'Your {website} email address was changed from {fromEmail} to {toEmail}' website=$SiteConfig.Title fromEmail=$OtherEmail toEmail=$Email %>
</p>
<% end_if %>

<% if $ProfileChangeAlertLink %>

<p><%t NSWDPC\\Authentication\\Services\\ConfigurationService.CONCERN_ABOUT_CHANGE 'If you did not make this change or are unsure of why this change was made, please contact us using the link below' %></p>

<p><a href="{$ProfileChangeAlertLink.XML}"><%t NSWDPC\\Authentication\\Services\\ConfigurationService.NOTIFY_PROFILE_CHANGE 'Contact Us' %></a></p>

<% end_if %>
