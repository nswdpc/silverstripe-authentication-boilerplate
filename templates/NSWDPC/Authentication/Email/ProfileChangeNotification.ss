
<h1><%t NSWDPC\\Members\\Configuration.HI 'Hi' %> {$Member.FirstName}</h1>

<% if $What %>
<p>
    <%t NSWDPC\\Members\\Configuration.YOUR_PROFILE_UPDATED 'Your profile was updated on' %> '{$SiteConfig.Title}'
    <%t NSWDPC\\Members\\Configuration.WITH_FOLLOWING_CHANGES 'with the following change(s)' %>
</p>
<ul>
<% loop $What %>
    <li>$Value.XML</li>
<% end_loop %>
</ul>
<% end_if %>

<p><%t NSWDPC\\Members\\Configuration.CONCERN_ABOUT_CHANGE 'If you did not make this change or are unsure of why this change was made, please contact us using the link below' %></p>

<% if ProfileChangeAlertLink %>
<p><a href="{$ProfileChangeAlertLink.XML}"><%t NSWDPC\\Members\\Configuration.NOTIFY_PROFILE_CHANGE 'Contact Us' %></a></p>
<% end_if %>
