
<h1><%t NSWDPC\\Members\\Configuration.HI 'Hi' %> {$Recipient.FirstName}</h1>

<% if $RequestState == 'started' %>
    <p><%t NSWDPC\\Members\\Configuration.ACCOUNT_RESET_MFA_STARTED 'An MFA account reset token was validated' %>.</p>
    <p><%t NSWDPC\\Members\\Configuration.ACCOUNT_RESET_MFA_STARTED_NEXT 'If completed, you will be notified' %>.</p>
<% else %>
    <%-- completed --%>
    <p><%t NSWDPC\\Members\\Configuration.ACCOUNT_RESET_MFA_COMPLETED 'An account was reset via the MFA reset process' %>.</p>
<% end_if %>

<p><%t NSWDPC\\Members\\Configuration.ACCOUNT_RESET_MFA_MEMBER_DETAILS 'Account details' %><p>
<ul>
<% with $ResettingMember %>
<li><%t NSWDPC\\Members\\Configuration.ACCOUNT_RESET_MFA_MEMBER_NAME 'Name' %>: {$Name.XML}</li>
<li><%t NSWDPC\\Members\\Configuration.ACCOUNT_RESET_MFA_MEMBER_EMAIL 'Email' %>: {$Email.XML}</li>
<% end_with %>
<li><%t NSWDPC\\Members\\Configuration.ACCOUNT_RESET_MFA_BROWSER 'Browser' %>: {$Browser.XML}</li>
<li><%t NSWDPC\\Members\\Configuration.ACCOUNT_RESET_MFA_REQUESTIP 'IP' %>: {$RequestIP.XML}</li>
</ul>

<p><%t NSWDPC\\Members\\Configuration.ACCOUNT_RESET_MFA_CHECKUP 'If these details are not as expected, please review the account' %>.</p>
