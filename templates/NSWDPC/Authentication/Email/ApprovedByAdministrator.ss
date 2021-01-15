
<h1><%t NSWDPC\\Members\\Configuration.HI 'Hi' %> {$Member.FirstName}</h1>

<p><%t NSWDPC\\Members\\Configuration.ADMINISTRATOR_APPROVED_YOUR_ACCOUNT 'Good news, your account was approved on' %> '{$SiteConfig.Title}' <%t NSWDPC\\Members\\Configuration.YOU_CAN_NOW_SIGN_IN 'and you can now sign in' %>.</p>

<% if $MemberProfileSignInLink %>
<p><a href="{$MemberProfileSignInLink}"><%t NSWDPC\\Members\\Configuration.PLEASE_APPROVE_LINK 'Sign in to use your account' %></a>.</p>
<% end_if %>
