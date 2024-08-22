<div class="mfa_help">
<% if $AuthenticationHelpHeading %>
    <% include NSWDPC/Authentication/HelpHeading HeadingLevel=$AuthenticationHelpHeadingLevel, Heading=$AuthenticationHelpHeading %>
<% end_if %>

<% if $AuthenticationHelpContent %>
<!-- specific -->
$AuthenticationHelpContent
<% else %>
<!-- default -->
<% include NSWDPC/Authentication/DefaultAuthenticationHelpContent %>
<% end_if %>
</div>
