<div class="message message-info">
    <% if $PasswordTitle %>
        <p>{$PasswordTitle.XML}</p>
    <% end_if %>
    <ul>
    <% if $MinLength %>
        <li>{$MinLength}</li>
    <% end_if %>
    <% if $MaxLength %>
        <li>{$MaxLength}</li>
    <% end_if %>
    <% if $MinTestScore && $CharacterStrengthTests %>
        <li>{$MinTestScore}
            <ul>
            <% loop $CharacterStrengthTests %>
                <li>{$Description}</li>
            <% end_loop %>
            </ul>
        </li>
    <% end_if %>
    <% if $RuleChecks %>
        <% loop $RuleChecks %>
            <li>{$Description}</li>
        <% end_loop %>
    <% end_if %>
    <% if $PwnageCheck %>
        <li>
            $PwnageCheck
            <% if $PwnageAttribution %><br>($PwnageAttribution)<% end_if %>
        </li>
    <% end_if %>
</div>
