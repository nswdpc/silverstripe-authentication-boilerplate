
<h1><%t NSWDPC\\Authentication\\Services\\ConfigurationService.HI 'Hi' %> {$Approver.FirstName}</h1>

<p><%t NSWDPC\\Authentication\\Services\\ConfigurationService.ADMINISTRATION_APPROVAL_REQUIRED 'You are receiving this email because a member on your website' %> '{$SiteConfig.Title}' requires their profile to be reviewed and optionally approved.</p>

<% if $Member %>
    <% with $Member %>
    <table>
        <tr><th>Name</th><td>{$Name.XML}</td></tr>
        <tr><th>Email</th><td>{$Email.XML}</td></tr>
    <% if $Groups %><tr><th>Groups</th><td><% loop $Groups %>$Title<% if not $Last %>, <% end_if %><% end_loop %></td></tr><% end_if %>
    </table>
    <% end_with %>
<% end_if %>

<p><a href="$ApprovePendingProfileLink"><%t NSWDPC\\Authentication\\Services\\ConfigurationService.PLEASE_APPROVE_LINK 'Review and optionally approve the pending profile here' %></a>.</p>
