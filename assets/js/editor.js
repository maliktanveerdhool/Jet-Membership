/**
 * MTD Membership - JetFormBuilder Register User Integration
 * Injects MTD Membership settings into the Register User action modal
 * 
 * Strategy: Intercept jfb.actions.registerAction BEFORE JFB registers the register_user action
 */
(function () {
    'use strict';

    console.log('[JET] Script loaded - waiting for JFB...');

    var checkCount = 0;
    var maxChecks = 100;
    var intercepted = false;

    /**
     * Create the MTD Membership UI section
     */
    function createMTDUI(React, components, settings, onChangeSettingObj) {
        var el = React.createElement;
        var WideLine = components.WideLine;
        var Label = components.Label;
        var Help = components.Help;
        var RowControl = components.RowControl;
        var StyledSelectControl = components.StyledSelectControl;

        var levels = window.JetMembershipData && window.JetMembershipData.levels
            ? window.JetMembershipData.levels
            : [{ value: '', label: 'No levels found' }];

        return el('div', {
            key: 'mtd-section',
            style: { marginTop: '15px' }
        },
            el(WideLine, null),
            el('div', {
                style: {
                    background: '#f0f6fc',
                    border: '1px solid #c3daf0',
                    borderLeft: '4px solid #2271b1',
                    borderRadius: '4px',
                    padding: '15px',
                    marginTop: '10px'
                }
            },
                el('div', {
                    style: {
                        display: 'flex',
                        alignItems: 'center',
                        marginBottom: '12px'
                    }
                },
                    el('span', {
                        style: {
                            background: '#2271b1',
                            color: '#fff',
                            padding: '2px 8px',
                            borderRadius: '3px',
                            fontSize: '11px',
                            fontWeight: 'bold',
                            marginRight: '10px'
                        }
                    }, 'JET'),
                    el('strong', { style: { fontSize: '13px' } }, 'Membership Assignment')
                ),
                el(RowControl, null, function (ctx) {
                    return el(React.Fragment, null,
                        el(Label, { htmlFor: ctx.id }, 'Assign Membership Level'),
                        el(StyledSelectControl, {
                            id: ctx.id,
                            value: settings.mtd_membership_level || '',
                            options: levels,
                            onChange: function (val) {
                                onChangeSettingObj({ mtd_membership_level: val });
                            }
                        })
                    );
                }),
                el(Help, { style: { marginTop: '8px' } },
                    'Select a membership level to assign to users registered via this form.'
                )
            )
        );
    }

    /**
     * Wrap the register_user action's edit component
     */
    function wrapEditComponent(originalEdit) {
        return function (props) {
            var React = window.React;
            var components = window.jfb && window.jfb.components;

            if (!React || !components) {
                console.warn('[JET] React or JFB components not available');
                return originalEdit(props);
            }

            var originalResult = originalEdit(props);
            var mtdUI = createMTDUI(React, components, props.settings, props.onChangeSettingObj);

            // Combine original with our UI
            return React.createElement(React.Fragment, null, originalResult, mtdUI);
        };
    }

    /**
     * Main check function - waits for JFB and intercepts registerAction
     */
    function checkAndIntercept() {
        checkCount++;

        // Check if jfb namespace exists
        if (!window.jfb) {
            if (checkCount < maxChecks) {
                setTimeout(checkAndIntercept, 50);
            } else {
                console.log('[JET] JFB not found after ' + maxChecks + ' checks');
            }
            return;
        }

        // Check if actions exists
        if (!window.jfb.actions) {
            if (checkCount < maxChecks) {
                setTimeout(checkAndIntercept, 50);
            }
            return;
        }

        // Already intercepted?
        if (intercepted) {
            return;
        }

        intercepted = true;
        console.log('[JET] JFB found! Setting up interception...');

        // Store the original registerAction
        var originalRegisterAction = window.jfb.actions.registerAction;

        if (typeof originalRegisterAction !== 'function') {
            console.error('[JET] registerAction is not a function:', typeof originalRegisterAction);
            return;
        }

        // Override registerAction
        window.jfb.actions.registerAction = function (actionConfig) {
            console.log('[JET] registerAction called for:', actionConfig ? actionConfig.type : 'unknown');

            if (actionConfig && actionConfig.type === 'register_user' && typeof actionConfig.edit === 'function') {
                console.log('[JET] Wrapping register_user edit component!');
                actionConfig.edit = wrapEditComponent(actionConfig.edit);
            }

            // Call original
            return originalRegisterAction.apply(this, arguments);
        };

        console.log('[JET] Interception complete!');
    }

    // Start checking immediately
    checkAndIntercept();

})();
