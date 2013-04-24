<?php

namespace Repositories;

use Doctrine\ORM\EntityRepository;

/**
 * Switcher
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class Switcher extends EntityRepository
{
    /**
     * The cache key for all switch objects
     * @var string The cache key for all switch objects
     */
    const ALL_CACHE_KEY = 'inex_switches';

    /**
     * Return an array of all switch objects from the database with caching
     *
     * @param bool $active If `true`, return only active switches
     * @param int $type If `0`, all types otherwise limit to specific type
     * @return array An array of all switch objects
     */
    public function getAndCache( $active = false, $type = 0 )
    {
        $dql = "SELECT s FROM Entities\\Switcher s WHERE 1=1";

        $key = self::ALL_CACHE_KEY;

        if( $active )
        {
            $dql .= " AND s.active = 1";
            $key .= '-active';
        }
        else
            $key .= '-all';

        if( $type )
        {
            $dql .= " AND s.switchtype = " . intval( $type );
            $key .= '-' . intval( $type );
        }
        else
            $key .= '-all';

        return $this->getEntityManager()->createQuery( $dql )
            ->useResultCache( true, 3600, $key )
            ->getResult();
    }

    /**
     * Return an array of all switch names where the array key is the switch id
     *
     * @param bool $active If `true`, return only active switches
     * @param int $type If `0`, all types otherwise limit to specific type
     * @return array An array of all switch names with the switch id as the key.
     */
    public function getNames( $active = false, $type = 0 )
    {
        $switches = [];
        foreach( $this->getAndCache( $active, $type ) as $a )
            $switches[ $a->getId() ] = $a->getName();

        return $switches;
    }


    public function getConfiguration( $switchid = null, $vlanid = null )
    {
        $q =
            "SELECT s.name AS switchname, s.id AS switchid,
                    sp.name AS portname,
                    pi.speed AS speed, pi.duplex AS duplex, pi.status AS portstatus,
                    c.name AS customer, c.id AS custid, c.autsys AS asn,
                    vli.rsclient AS rsclient,
                    v.name AS vlan,
                    ipv4.address AS ipv4address, ipv6.address AS ipv6address

            FROM \\Entities\\VlanInterface vli
                JOIN vli.IPv4Address ipv4
                LEFT JOIN vli.IPv6Address ipv6
                LEFT JOIN vli.Vlan v
                LEFT JOIN vli.VirtualInterface vi
                LEFT JOIN vi.Customer c
                LEFT JOIN vi.PhysicalInterfaces pi
                LEFT JOIN pi.SwitchPort sp
                LEFT JOIN sp.Switcher s

            WHERE 1=1 ";

        if( $switchid !== null )
            $q .= 'AND s.id = ' . intval( $switchid ) . ' ';

        if( $vlanid !== null )
            $q .= 'AND v.id = ' . intval( $vlanid ) . ' ';

        $q .= "ORDER BY customer ASC";

        return $this->getEntityManager()->createQuery( $q )->getArrayResult();
    }


    /**
     * Get all active switches as Doctrine2 objects
     *
     * @return array
     */
    public function getActive()
    {
        $q = "SELECT s FROM \\Entities\\Switcher s WHERE s.active = 1";
        return $this->getEntityManager()->createQuery( $q )->getResult();
    }

}
