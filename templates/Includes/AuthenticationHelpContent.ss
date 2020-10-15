<div class="mfa_help">
<% if $AuthenticationHelpHeading %>
    <% include AuthenticationHelpHeading HeadingLevel=$AuthenticationHelpHeadingLevel, Heading=$AuthenticationHelpHeading %>
<% end_if %>

<% if $AuthenticationHelpContent %>
<!-- specific -->
$AuthenticationHelpContent
<% else %>
<!-- default -->
<% include DefaultAuthenticationHelpContent %>
<% end_if %>
</div>
