<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace PrestaShopBundle\Form\Admin\Sell\Product\Pricing;

use Currency;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Type;

/**
 * Form type containing price fields for Pricing tab
 */
class PricingType extends TranslatorAwareType
{
    /**
     * @var array
     */
    private $taxRuleGroupChoices;

    /**
     * @var array
     */
    private $taxRuleGroupChoicesAttributes;

    /**
     * @var Currency
     */
    private $defaultCurrency;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var bool
     */
    private $taxEnabled;

    /**
     * @param TranslatorInterface $translator
     * @param array $locales
     * @param array $taxRuleGroupChoices
     * @param array $taxRuleGroupChoicesAttributes
     * @param Currency $defaultCurrency
     * @param RouterInterface $router
     * @param bool $taxEnabled
     */
    public function __construct(
        TranslatorInterface $translator,
        array $locales,
        array $taxRuleGroupChoices,
        array $taxRuleGroupChoicesAttributes,
        Currency $defaultCurrency,
        RouterInterface $router,
        bool $taxEnabled
    ) {
        parent::__construct($translator, $locales);
        $this->taxRuleGroupChoices = $taxRuleGroupChoices;
        $this->taxRuleGroupChoicesAttributes = $taxRuleGroupChoicesAttributes;
        $this->defaultCurrency = $defaultCurrency;
        $this->router = $router;
        $this->taxEnabled = $taxEnabled;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('retail_price', RetailPriceType::class)
            ->add('tax_rules_group_id', ChoiceType::class, [
                'choices' => $this->taxRuleGroupChoices,
                'required' => false,
                // placeholder false is important to avoid empty option in select input despite required being false
                'placeholder' => false,
                'choice_attr' => $this->taxRuleGroupChoicesAttributes,
                'attr' => [
                    'data-toggle' => 'select2',
                    'data-minimumResultsForSearch' => '7',
                    'data-tax-enabled' => $this->taxEnabled,
                ],
                'label' => $this->trans('Tax rule', 'Admin.Catalog.Feature'),
                'help' => !$this->taxEnabled ? $this->trans('Tax feature is disabled, it will not affect price tax included.', 'Admin.Catalog.Feature') : '',
                'external_link' => [
                    'text' => $this->trans('[1]Manage tax rules[/1]', 'Admin.Catalog.Feature'),
                    'href' => $this->router->generate('admin_taxes_index'),
                    'align' => 'right',
                ],
                'modify_all_shops' => true,
            ])
            ->add('unit_price', UnitPriceType::class)
            ->add('on_sale', CheckboxType::class, [
                'required' => false,
                'label' => $this->trans(
                    'Display the "On sale!" flag on the product page, and on product listings.',
                    'Admin.Catalog.Feature'
                ),
                'modify_all_shops' => true,
            ])
            ->add('wholesale_price', MoneyType::class, [
                'required' => false,
                'label' => $this->trans('Cost price (tax excl.)', 'Admin.Catalog.Feature'),
                'label_tag_name' => 'h3',
                'label_help_box' => $this->trans('The cost price is the price you paid for the product. Do not include the tax. It should be lower than the retail price: the difference between the two will be your margin.', 'Admin.Catalog.Help'),
                'attr' => ['data-display-price-precision' => self::PRESTASHOP_DECIMALS],
                'currency' => $this->defaultCurrency->iso_code,
                'modify_all_shops' => true,
                'constraints' => [
                    new NotBlank(),
                    new Type(['type' => 'float']),
                    new PositiveOrZero(),
                ],
            ])
            ->add('specific_prices', SpecificPricesType::class, [
                'label' => $this->trans('Specific prices', 'Admin.Catalog.Feature'),
                'label_tag_name' => 'h2',
                'label_help_box' => $this->trans('Set specific prices for customers meeting certain conditions.', 'Admin.Catalog.Help'),
            ])
            ->add('priority_management', ProductSpecificPricePriorityType::class, [
                'label' => $this->trans('Priority management', 'Admin.Catalog.Feature'),
                'label_tag_name' => 'h2',
                'label_help_box' => $this->trans('Define which condition should apply first when a customer is eligible for multiple specific prices.', 'Admin.Catalog.Help'),
            ])
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'label' => false,
            'required' => false,
        ]);
    }
}
