<div class="mfa_help">
<% if $AuthenticationHelpHeading %>
    <% include NSWDPC/MFA/HelpHeading HeadingLevel=$AuthenticationHelpHeadingLevel, Heading=$AuthenticationHelpHeading %>
<% end_if %>

<% if $AuthenticationHelpContent %>
<!-- specific -->
$AuthenticationHelpContent
<% else %>
<!-- default -->
<% include DefaultAuthenticationHelpContent %>
<% end_if %>
</div>
