const button = document.querySelector('.menu-button');
const navigation = document.querySelector('.main-navigation');

button?.addEventListener('click', () => {
    const open = button.getAttribute('aria-expanded') === 'true';
    button.setAttribute('aria-expanded', String(!open));
    navigation?.classList.toggle('is-open', !open);
});

const eventForm = document.querySelector('.event-form');
document.querySelectorAll('.edit-event').forEach((editButton) => {
    editButton.addEventListener('click', () => {
        if (!eventForm) return;
        const event = JSON.parse(editButton.dataset.event || '{}');
        ['id', 'title', 'date', 'time', 'location', 'description', 'link'].forEach((name) => {
            const field = eventForm.elements.namedItem(name);
            if (field) field.value = event[name] || '';
        });
        eventForm.querySelector('button[type="submit"]').textContent = 'Änderungen speichern';
        eventForm.scrollIntoView({behavior: 'smooth'});
    });
});
