$(() => {
    const eventSelect = '#events-select';
    const $pjaxStudents = $(pjaxStudents);

    const $form = $('#add-student-form');
    const $button = $('.btn-create-student');

    $form
        .off('change', eventSelect)
        .on('change', eventSelect, () => {
            window.history.pushState({}, '', setEventParam(window.location.href, $(eventSelect).val()));

            $pjaxStudents
                .off('pjax:beforeSend')
                .on('pjax:beforeSend', () => CommonUtils.showLoadingPlaceholderTable(pjaxStudents, 'Студенты'));

            updateStudentsList().then(() => {
                $pjaxStudents.off('pjax:beforeSend');
            });

            if (sourceSSE) sourceSSE.close();
            if ($(eventSelect).val()) sourceSSE = CommonUtils.connectDataSSE(setEventParam(`${window.location.origin}${url}/sse-data-updates`, $(eventSelect).val()), updateStudentsList);

            $(eventSelect).removeClass('is-valid is-invalid');

            // $pjaxStudents.off('pjax:beforeSend');
        });

    const loadChoicesDate = (url) => {
        let currentEvent = $(eventSelect).val();

        CommonUtils.performAjax({
            url: url,
            method: 'GET',
            async: false,
            success(data) {
                const hasGroup = data.hasGroup;
                const events = data.events;
                const choices = [choicesMap.get('events-select')];
                choices.forEach((el) => {
                    if (el) {
                        const $select = $(el.passedElement.element);
                        const storeChoices = el._store._state.choices;
                        const currentValue = $select.val() ?? '';

                        storeChoices.forEach(option => {
                            if (option.value !== '') el.removeChoice(option.value);
                        });
                        $select.find('option').not('[value=""]').remove();

                        if (hasGroup) {
                            const groupChoices = events.map((group, index) => ({
                                label: group.group,
                                id: index,
                                choices: group.items.map(item => ({
                                    value: item.value + '',
                                    label: item.label
                                }))
                            }));

                            el.setChoices(groupChoices, 'value', 'label', false);
                        } else {
                            const flatChoices = events.map(item => ({
                                value: item.value + '',
                                label: item.label
                            }));

                            el.setChoices(flatChoices, 'value', 'label', false);
                        }

                        const allValues = hasGroup
                            ? events.flatMap(g => g.items.map(i => i.value + ''))
                            : events.map(i => i.value + '');

                        if (allValues.includes(currentValue)) {
                            el.setChoiceByValue(currentValue);
                            $select.val(currentValue);
                        } else {
                            el.setChoiceByValue('');
                            $select.val('');
                            $select.removeClass('is-valid is-invalid');
                        }
                        window.history.pushState({}, '', setEventParam(window.location.href, $select.val()));
                    }
                })
            },
        });

        if (currentEvent != $(eventSelect).val()) {
            $(eventSelect).trigger('change');
        }

        updateStudentsList();
    }

    CommonUtils.connectDataSSE(`${urlEvent}/sse-data-updates`, loadChoicesDate, `${url}/all-events`);

    $form.on('beforeSubmit', function(e) {
        e.preventDefault();

        CommonUtils.performAjax({
            url: $form.prop('action'),
            method: 'POST',
            data: $form.serialize(),
            beforeSend: () => {
                CommonUtils.toggleButtonState($button, true);
            },
            success: (data) => {
                if (data.success) {
                    $form.trigger("reset");
                    $form.yiiActiveForm('resetForm');
                    document.activeElement.blur();
                    let currentEvent = getEventParam(window.location.href);
                    $(eventSelect).val(currentEvent);
                    choicesMap.get('events-select').setChoiceByValue(currentEvent);
                    updateStudentsList();
                } else if (data.errors) {
                    $form.yiiActiveForm('updateMessages', data.errors, true);
                }
            },
            complete: () => {
                CommonUtils.toggleButtonState($button, false);
            }
        });

        return false;
    });
});