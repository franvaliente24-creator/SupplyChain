// Shared profile update function for session management
function applyUserToProfile(user) {
    const email = user.email || 'admin@example.com';
    const role = user.role || 'Administrator';
    const rawUsername = user.username || '';
    const atIdx = email.indexOf('@');
    const namePart = rawUsername
        ? rawUsername
        : (atIdx > 0 ? email.slice(0, atIdx) : email);

    const initials = namePart
        .split(/[._-\s]+/)
        .filter(Boolean)
        .map(p => p.charAt(0).toUpperCase())
        .slice(0, 2)
        .join('') || 'AU';

    const displayName = rawUsername
        ? rawUsername.replace(/[._-\s]+/g, ' ').replace(/\b\w/g, c => c.toUpperCase()).trim()
        : namePart.replace(/[._-]+/g, ' ').replace(/\b\w/g, c => c.toUpperCase()).trim();

    const roleLabel = role.charAt(0).toUpperCase() + role.slice(1).toLowerCase();

    const avatarDiv = document.querySelector('#profile-dropdown-toggle > div');
    if (avatarDiv) {
        avatarDiv.title = displayName;
        
        // Update the fallback span with initials
        const fallbackSpan = avatarDiv.querySelector('span');
        if (fallbackSpan) {
            fallbackSpan.textContent = initials;
            // Reset to hidden, let image's onerror handle showing it
            fallbackSpan.style.display = 'none';
        }
        
        // Update image source and try to show it
        const img = avatarDiv.querySelector('img');
        if (img) {
            img.src = '/SupplyChain/img/profile.jpg';
            img.style.display = 'block';
        }
    }

    const nameEls = document.querySelectorAll('#profile-dropdown-toggle span.font-label');
    if (nameEls[0]) nameEls[0].textContent = displayName || 'Admin User';
    const roleEls = document.querySelectorAll('#profile-dropdown-toggle span.text-xs');
    if (roleEls[0]) roleEls[0].textContent = roleLabel;

    const mobileName = document.querySelector('#profile-dropdown-menu p.text-sm.font-semibold');
    if (mobileName) mobileName.textContent = displayName || 'Admin User';
    const mobileEmail = document.querySelector('#profile-dropdown-menu p.text-xs.text-on-surface-variant.truncate');
    if (mobileEmail) mobileEmail.textContent = email;
}
