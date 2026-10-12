
document.getElementById('tagsInput').addEventListener('input', function() {
    const tags = this.value.split(',').map(t => t.trim()).filter(Boolean);
    document.getElementById('tagPreview').innerHTML =
        tags.map(t => `<span class="badge bg-primary-subtle text-primary border border-primary-subtle">${t}</span>`).join('');
});
