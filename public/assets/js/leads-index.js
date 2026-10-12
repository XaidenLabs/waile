
function openFunnelModal(leadId, leadName) {
    document.getElementById('funnelLeadId').value   = leadId;
    document.getElementById('funnelLeadName').textContent = leadName;
    new bootstrap.Modal(document.getElementById('funnelModal')).show();
}
