document.querySelectorAll('[data-phone]').forEach((field) => {
    const validate = () => {
        const digits = field.value.replace(/[\s-]/g, '');
        field.setCustomValidity(field.value && !/^[0-9]{9,10}$/.test(digits)
            ? 'กรุณากรอกเบอร์โทรศัพท์ 9–10 หลัก' : '');
    };
    field.addEventListener('input', validate);
    validate();
});
