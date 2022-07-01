
<h1><%t NSWDPC\\Members\\Configuration.HI 'Hi' %> {$Member.FirstName}</h1>

<p><%t NSWDPC\\Members\\Configuration.ADMINISTRATOR_APPROVED_YOUR_ACCOUNT 'Good news, your account was approved on {siteName}' siteName=$SiteConfig.Title %>.</p>

<% if $MemberProfileSignInLink %>
<p><a href="{$MemberProfileSignInLink}"><%t NSWDPC\\Members\\Configuration.PLEASE_APPROVE_LINK 'Sign in to use your account' %></a>.</p>
<% end_if %>

<% if $ProfileContactLink %>
<p><a href="{$ProfileContactLink.XML}"><%t NSWDPC\\Members\\Configuration.NOTIFY_PROFILE_CONTACT 'Contact us if you need assistance' %></a></p>
<% end_if %>
