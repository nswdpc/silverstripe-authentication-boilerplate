
<h1><%t NSWDPC\\Authentication\\Services\\ConfigurationService.HI 'Hi' %> {$Member.FirstName}</h1>

<p><%t NSWDPC\\Authentication\\Services\\ConfigurationService.ADMINISTRATOR_APPROVED_YOUR_ACCOUNT 'Good news, your account was approved on {siteName}' siteName=$SiteConfig.Title %>.</p>

<% if $MemberProfileSignInLink %>
<p><a href="{$MemberProfileSignInLink}"><%t NSWDPC\\Authentication\\Services\\ConfigurationService.PLEASE_APPROVE_LINK 'Sign in to use your account' %></a>.</p>
<% end_if %>

<% if $ProfileContactLink %>
<p><a href="{$ProfileContactLink.XML}"><%t NSWDPC\\Authentication\\Services\\ConfigurationService.NOTIFY_PROFILE_CONTACT 'Contact us if you need assistance' %></a></p>
<% end_if %>
