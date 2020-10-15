<% if $AuthenticationHelpShowAbove %>
    <% include AuthenticationHelpContent %>
<% end_if %>
    $Content
<% if not $AuthenticationHelpShowAbove %>
    <% include AuthenticationHelpContent %>
<% end_if %>
