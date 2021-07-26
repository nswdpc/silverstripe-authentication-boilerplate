<% if $AuthenticationHelpShowAbove %>
    <% include NSWDPC/MFA/HelpContent %>
<% end_if %>

<% if $supportsElemental && $ElementalArea && $ElementalArea.Elements.count > 0 %>
    {$ElementalArea}
<% else %>
    {$Content}
<% end_if %>
