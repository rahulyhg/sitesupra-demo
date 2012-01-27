<?php

namespace Supra\Payment\Entity\Order;

use Supra\Database;
use Supra\Payment\Order\OrderStatus;
use Supra\Payment\Entity\Transaction\Transaction;
use Supra\Payment\Entity\Currency\Currency;
use Supra\Payment\Entity\Order\OrderItem;
use Supra\Payment\Entity\Order\OrderPaymentProviderItem;
use Supra\Payment\Product\ProductAbstraction;
use Supra\User\Entity\AbstractUser;
use Doctrine\Common\Collections\ArrayCollection;
use \DateTime;
use \Locale;
use Supra\ObjectRepository\ObjectRepository;

/**
 * @Entity 
 */
class ShopOrder extends Order
{

	/**
	 * @OneToOne(targetEntity="Supra\Payment\Entity\Transaction\Transaction")
	 * @JoinColumn(name="transactionId", referencedColumnName="id")
	 * @var Transaction
	 */
	protected $transaction;

	/**
	 * @return Transaction
	 */
	public function getTransaction()
	{
		return $this->transaction;
	}

	/**
	 * @param Transaction $transaction 
	 */
	public function setTransaction(Transaction $transaction)
	{
		$this->transaction = $transaction;
	}

	/**
	 * @return string 
	 */
	public function getPaymentProviderId()
	{
		$paymentProviderId = null;

		$transaction = $this->getTransaction();

		if ( ! empty($transaction)) {
			return $transaction->getPaymentProviderId();
		}

		return $paymentProviderId;
	}

	public function addToPaymentEntityParameters($phaseName, $data)
	{
		$transaction = $this->getTransaction();
		$transaction->addToParameters($phaseName, $data);
	}

	public function setStatus($status)
	{
		OrderStatus::validate($status);
		$this->status = $status;
	}

}